<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerRoutes();
    }

    /**
     * Register the routes for the application.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::macro('cde', function ($prefix) {
            Route::prefix($prefix)->group(function () {
                Route::get('indice-revelador/{post_id}', function ($post_id) {
                    $args = [
                        'post_type' => 'cde',
                        'posts_per_page' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                    ];

                    $all_posts = get_posts($args);
                    $post_map = [];

                    foreach ($all_posts as $post) {
                        $post_map[$post->ID] = (object) [
                            'id' => $post->ID,
                            'title' => $post->post_title,
                            'permalink' => get_permalink($post->ID),
                            'parent' => $post->post_parent,
                            'children' => [],
                        ];
                    }

                    $tree = [];
                    foreach ($post_map as $id => &$node) {
                        if ($node->parent && isset($post_map[$node->parent])) {
                            $post_map[$node->parent]->children[] = &$node;
                        } else {
                            $tree[] = &$node;
                        }
                    }

                    $course_tree = array_filter($tree, function ($item) use ($post_id) {
                        return $item->id == $post_id;
                    });

                    if (empty($course_tree)) {
                        return response()->json(['html' => '<p>No se encontraron lecciones para este revelador.</p>']);
                    }

                    $html = view('partials.course-index-item', ['items' => $course_tree, 'level' => 0])->render();

                    return response()->json(['html' => $html]);
                });
            });
        });

        Route::cde('espaciosutil/v1');
    }
}
