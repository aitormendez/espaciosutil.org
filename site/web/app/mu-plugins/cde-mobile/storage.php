<?php
namespace EspacioSutil\Mobile;

final class Schema
{
    public static function table(string $name): string { global $wpdb; return $wpdb->prefix . 'cde_mobile_' . $name; }
    public static function ready(): bool { return get_option('cde_mobile_schema') === '1'; }
    public static function install(): void
    {
        if (!defined('WP_CLI') || !WP_CLI || WP_ENV !== 'staging' || gethostname() !== 'espacio-sutil-staging') throw new \RuntimeException('Migración sólo en staging.');
        global $wpdb;
        $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$wpdb->usermeta));
        if ($engine!=='InnoDB') throw new \RuntimeException('Se requiere usermeta transaccional.');
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $collate=$wpdb->get_charset_collate();
        $sessions=self::table('sessions'); $limits=self::table('limits'); $revisions=self::table('revisions'); $receipts=self::table('receipts');
        dbDelta("CREATE TABLE $sessions (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            access_hash char(64) NOT NULL,
            refresh_hash char(64) NOT NULL,
            consumed longtext NOT NULL,
            password_hash char(64) NOT NULL,
            access_expires bigint NOT NULL,
            expires bigint NOT NULL,
            revoked tinyint NOT NULL DEFAULT 0,
            device_label varchar(80) NOT NULL,
            PRIMARY KEY  (id), UNIQUE KEY access_hash (access_hash), UNIQUE KEY refresh_hash (refresh_hash)
        ) ENGINE=InnoDB $collate;");
        dbDelta("CREATE TABLE $limits (
            bucket char(64) NOT NULL,
            hits int NOT NULL,
            expires bigint NOT NULL,
            PRIMARY KEY  (bucket)
        ) ENGINE=InnoDB $collate;");
        dbDelta("CREATE TABLE $revisions (
            user_id bigint unsigned NOT NULL,
            resource varchar(120) NOT NULL,
            revision bigint unsigned NOT NULL,
            updated_at varchar(30) NOT NULL,
            PRIMARY KEY  (user_id,resource)
        ) ENGINE=InnoDB $collate;");
        dbDelta("CREATE TABLE $receipts (
            user_id bigint unsigned NOT NULL,
            operation_id char(36) NOT NULL,
            body_hash char(64) NOT NULL,
            response longtext NOT NULL,
            created bigint NOT NULL,
            PRIMARY KEY  (user_id,operation_id)
        ) ENGINE=InnoDB $collate;");
        foreach ([$sessions,$limits,$revisions,$receipts] as $table) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table))) !== $table) throw new \RuntimeException('Esquema incompleto.');
        }
        update_option('cde_mobile_schema','1',false);
    }
    public static function query(string $sql): int|bool
    {
        global $wpdb; $result=$wpdb->query($sql);
        if ($result===false) throw new \RuntimeException('Error de persistencia.');
        return $result;
    }
}

final class Sessions
{
    public static function token(): string { return rtrim(strtr(base64_encode(random_bytes(32)),'+/','-_'),'='); }
    private static function fingerprint(int $user): string { return hash('sha256',(string) get_userdata($user)->user_pass); }
    public static function limit(string $key,int $max): bool
    {
        global $wpdb; $table=Schema::table('limits'); $window=(int) floor(time()/900);
        $bucket=hash_hmac('sha256',$key.':'.$window,wp_salt('auth'));
        Schema::query($wpdb->prepare("INSERT INTO $table (bucket,hits,expires) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1",$bucket,($window+1)*900));
        return (int) $wpdb->get_var($wpdb->prepare("SELECT hits FROM $table WHERE bucket=%s",$bucket)) <= $max;
    }
    private static function pair(string $access,string $refresh,int $expires): array
    {
        return ['token_type'=>'Bearer','access_token'=>$access,'refresh_token'=>$refresh,'access_expires_at'=>gmdate('c',min(time()+900,$expires)),'refresh_expires_at'=>gmdate('c',$expires)];
    }
    public static function login(array $body): array|\WP_Error
    {
        global $wpdb;
        if (!self::limit('login-ip:'.($_SERVER['REMOTE_ADDR']??''),60) || !self::limit('login-account:'.strtolower($body['login']),12)) return Access::error('rate_limited',429);
        $user=wp_authenticate($body['login'],$body['password']);
        if (is_wp_error($user)) return new \WP_Error('invalid_credentials','El usuario o la contraseña no son correctos.',['status'=>401]);
        $access=self::token();$refresh=self::token();$expires=time()+30*86400;
        $ok=$wpdb->insert(Schema::table('sessions'),['user_id'=>$user->ID,'access_hash'=>hash('sha256',$access),'refresh_hash'=>hash('sha256',$refresh),'consumed'=>'[]','password_hash'=>self::fingerprint($user->ID),'access_expires'=>time()+900,'expires'=>$expires,'device_label'=>$body['device_label']]);
        if ($ok===false) throw new \RuntimeException('No se pudo crear sesión.');
        return self::pair($access,$refresh,$expires);
    }
    public static function user(string $token): int|\WP_Error
    {
        global $wpdb; $table=Schema::table('sessions');
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/D',$token)) return Access::error('invalid_session',401);
        $s=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE access_hash=%s",hash('sha256',$token)));
        if (!$s || $s->revoked || $s->access_expires<=time() || $s->expires<=time() || !get_userdata($s->user_id) || !hash_equals($s->password_hash,self::fingerprint((int)$s->user_id))) return Access::error('invalid_session',401);
        return (int)$s->user_id;
    }
    public static function refresh(string $token,bool $logout=false): array|\WP_Error|null
    {
        global $wpdb; $table=Schema::table('sessions'); $hash=hash('sha256',$token);
        if (!self::limit('refresh-ip:'.($_SERVER['REMOTE_ADDR']??''),120)) return Access::error('rate_limited',429);
        Schema::query('START TRANSACTION');
        try {
            // La búsqueda del hash consumido detecta reutilización incluso después de varias rotaciones.
            $s=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE refresh_hash=%s OR consumed LIKE %s LIMIT 1 FOR UPDATE",$hash,'%"'.$hash.'"%'));
            if (!$s) { Schema::query('COMMIT'); return $logout?null:Access::error('invalid_session',401); }
            if ($logout || !hash_equals($s->refresh_hash,$hash) || $s->revoked || $s->expires<=time() || !get_userdata($s->user_id) || !hash_equals($s->password_hash,self::fingerprint((int)$s->user_id))) {
                Schema::query($wpdb->prepare("UPDATE $table SET revoked=1 WHERE id=%d",$s->id));Schema::query('COMMIT');
                return $logout?null:Access::error('invalid_session',401);
            }
            $consumed=json_decode($s->consumed,true); $consumed[]=$hash;
            if (count($consumed)>10000) { Schema::query($wpdb->prepare("UPDATE $table SET revoked=1 WHERE id=%d",$s->id));Schema::query('COMMIT');return Access::error('invalid_session',401); }
            $access=self::token();$refresh=self::token();
            $ok=$wpdb->update($table,['access_hash'=>hash('sha256',$access),'refresh_hash'=>hash('sha256',$refresh),'consumed'=>wp_json_encode($consumed),'access_expires'=>min(time()+900,(int)$s->expires)],['id'=>$s->id]);
            if ($ok===false) throw new \RuntimeException('No se pudo renovar sesión.');
            Schema::query('COMMIT');return self::pair($access,$refresh,(int)$s->expires);
        } catch (\Throwable $e) { $wpdb->query('ROLLBACK'); throw $e; }
    }
}
