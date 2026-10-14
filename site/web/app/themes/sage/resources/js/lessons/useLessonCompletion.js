import { useEffect } from 'react';
import { canAutoCompleteLesson, resetLessonAutoCompletion, setLessonCompleted } from '../courses/mark-complete.js';
import { observeLessonCompletion } from './mediaCompletion.js';

export default function useLessonCompletion(playerRef, lessonId, isCompleted) {
  useEffect(() => {
    const player = playerRef.current;
    if (!player || !lessonId) return;
    return observeLessonCompletion(player, {
      onReplay: () => resetLessonAutoCompletion(lessonId),
      canComplete: () => canAutoCompleteLesson(lessonId, isCompleted),
      complete: () => setLessonCompleted(lessonId, true, { automatic: true }),
    });
  }, [playerRef, lessonId, isCompleted]);
}
