import importlib.util
from pathlib import Path
import tempfile
import unittest
import wave

spec = importlib.util.spec_from_file_location('practice_audio', Path(__file__).with_name('generate-practice-audio.py'))
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

class PracticeAudioBoundaryTests(unittest.TestCase):
    def test_boundary_uses_exact_codec_samples(self):
        self.assertEqual(module.generated_boundary(269, 134, 1920, 516480), 259200)

    def test_rejects_approximate_duration(self):
        with self.assertRaises(ValueError):
            module.generated_boundary(269, 134, 1920, 516479)

    def test_rejects_invalid_generated_frame_count(self):
        for count in (0, -1, 270):
            with self.assertRaises(ValueError):
                module.generated_boundary(269, count, 1920, 516480)

    def test_reference_copy_preserves_samples_and_adds_only_silence(self):
        with tempfile.TemporaryDirectory() as tmp:
            source, target = Path(tmp)/'original.wav', Path(tmp)/'prepared.wav'
            frames = b'\x01\x00\xff\xff' * 12000
            with wave.open(str(source), 'wb') as audio:
                audio.setnchannels(1); audio.setsampwidth(2); audio.setframerate(24000); audio.writeframes(frames)
            original = source.read_bytes()
            module.prepare_reference(source, target)
            self.assertEqual(source.read_bytes(), original)
            with wave.open(str(target), 'rb') as audio:
                self.assertEqual(audio.getnframes(), len(frames)//2 + 11520)
                self.assertEqual(audio.readframes(audio.getnframes()), frames + bytes(23040))

if __name__ == '__main__':
    unittest.main()
