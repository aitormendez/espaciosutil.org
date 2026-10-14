#!/usr/bin/env python3
"""Offline Qwen3/MLX narrator. Read the existing Ivan bundle, write only --output.
Run with the bundle's Python environment; never invokes its training/evaluation main.
"""
import argparse
import hashlib
import json
import math
import os
from pathlib import Path
import subprocess
import sys
import time
import wave

os.environ['HF_HUB_OFFLINE'] = '1'
os.environ['HF_HUB_DISABLE_TELEMETRY'] = '1'
os.environ['PYTHONDONTWRITEBYTECODE'] = '1'
sys.dont_write_bytecode = True

def digest(path):
    return hashlib.sha256(Path(path).read_bytes()).hexdigest()

def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('plan', type=Path)
    p.add_argument('--voice-bundle', required=True, type=Path)
    p.add_argument('--output', required=True, type=Path)
    p.add_argument('--limit', type=int)
    args = p.parse_args()
    plan = json.loads(args.plan.read_text())
    assert plan['schema'] == 'cde.practice.audio-plan' and plan['version'] == 1
    assert len(plan['revision']) == 64 and all(c in '0123456789abcdef' for c in plan['revision'])
    bundle = args.voice_bundle.resolve()
    selection = json.loads((bundle/'seleccion-checkpoint.json').read_text())
    checkpoint = bundle/selection['checkpoint']
    assert selection['adapter_strength'] == 0.25 and selection['reference_id'] == 'F063'
    assert digest(checkpoint/'adapters.safetensors') == selection['adapter_sha256']
    sys.path.insert(0, str(bundle))
    from entrenar import load_base, add_adapters, reload_adapter, LoRALinear, DATA, mx, np
    output = args.output.resolve()/str(int(plan['post_id']))/plan['revision']
    output.mkdir(parents=True, exist_ok=True)
    model = load_base()
    add_adapters(model)
    reload_adapter(model, checkpoint)
    for _, module in model.named_modules():
        if isinstance(module, LoRALinear): module.scale *= 0.25
    model.eval()
    reference = (DATA/'textos/F063.txt').read_text().strip()
    for i, screen in enumerate(plan['screens'][:args.limit]):
        text = screen['speech']
        expected = dict(text=text, adapter_sha256=selection['adapter_sha256'], adapter_strength=0.25,
                        reference_sha256=digest(DATA/'audios/F063.wav'), seed=20261014+i)
        mp3, meta = output/f'{i}.mp3', output/f'{i}.json'
        if mp3.exists() and meta.exists():
            prior = json.loads(meta.read_text())
            assert prior['input'] == expected and prior['sha256'] == digest(mp3)
            continue
        mx.random.seed(expected['seed'])
        start = time.monotonic()
        chunks = list(model.generate(text=text, ref_audio=str(DATA/'audios/F063.wav'), ref_text=reference,
            lang_code='Spanish', temperature=0.7, repetition_penalty=1.5, top_k=50, top_p=1.0,
            max_tokens=max(350, math.ceil(len(text.split())/2.0*12.5*2.5)), verbose=False))
        samples = np.concatenate([np.asarray(c.audio, dtype=np.float32) for c in chunks])
        assert all(c.sample_rate == 24000 for c in chunks) and len(samples) > 2400 and np.isfinite(samples).all()
        wav = output/f'{i}.wav'
        with wave.open(str(wav), 'wb') as f:
            f.setnchannels(1); f.setsampwidth(2); f.setframerate(24000)
            f.writeframes((np.clip(samples, -1, 1)*32767).astype('<i2').tobytes())
        temp = output/f'{i}.tmp.mp3'
        subprocess.run(['ffmpeg','-v','error','-nostdin','-y','-i',str(wav),'-codec:a','libmp3lame','-b:a','96k',str(temp)],check=True)
        temp.replace(mp3)
        result = dict(input=expected, sha256=digest(mp3), seconds=len(samples)/24000,
            generation_seconds=round(time.monotonic()-start,2), peak=float(np.max(np.abs(samples))),
            clipping_samples=int(np.sum(np.abs(samples)>1)), tokens=sum(c.token_count for c in chunks))
        meta.write_text(json.dumps(result, ensure_ascii=False, indent=2)+'\n')
        print(json.dumps(dict(screen=i+1,total=len(plan['screens']),seconds=result['seconds'],generation_seconds=result['generation_seconds'])),flush=True)
        del samples, chunks
        mx.clear_cache()
    (output/'manifest.json').write_text(json.dumps(dict(plan=plan,voice='ivan-qwen3-epoch3-025',audio_review='pending'),ensure_ascii=False,indent=2)+'\n')

if __name__ == '__main__': main()
