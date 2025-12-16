import { gsap } from 'gsap';

const ensureQuizStyles = (() => {
  let injected = false;

  return () => {
    if (injected) return;
    injected = true;

    const style = document.createElement('style');
    style.textContent = `
      #lesson-quiz .quiz-progress {
        position: relative;
        height: 8px;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        overflow: hidden;
      }
      #lesson-quiz .quiz-progress-bar {
        height: 100%;
        width: var(--percent, 0%);
        background: linear-gradient(90deg, #a855f7, #67e8f9);
        transition: width 250ms ease;
      }
      #lesson-quiz .quiz-slide-in {
        animation: quizSlideIn 220ms ease;
      }
      #lesson-quiz .quiz-slide-out {
        animation: quizSlideOut 200ms ease;
      }
      @keyframes quizSlideIn {
        from { opacity: 0; transform: translateX(12px); }
        to { opacity: 1; transform: translateX(0); }
      }
      @keyframes quizSlideOut {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(-12px); }
      }
      #lesson-quiz .quiz-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 16px;
        flex-wrap: wrap;
      }
      #lesson-quiz .quiz-dots {
        display: flex;
        gap: 8px;
        flex: 1;
        flex-wrap: wrap;
      }
      #lesson-quiz .quiz-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.4);
        background: rgba(255,255,255,0.15);
        cursor: pointer;
        transition: all 160ms ease;
      }
      #lesson-quiz .quiz-dot.is-active {
        background: linear-gradient(90deg, #a855f7, #67e8f9);
        border-color: transparent;
        box-shadow: 0 0 0 4px rgba(255,255,255,0.06);
      }
      #lesson-quiz .quiz-dot:hover {
        transform: translateY(-1px);
      }
    `;
    document.head.appendChild(style);
  };
})();

const parseQuizProps = (container) => {
  try {
    return JSON.parse(container.dataset.quizProps || '{}');
  } catch (e) {
    return {};
  }
};

const getApiConfig = () => {
  const root = window.wpApiSettings?.root;
  const nonce = window.wpApiSettings?.nonce;

  if (!root || !nonce) {
    return null;
  }

  return { root, nonce };
};

const normalizeQuestions = (questions = []) =>
  questions
    .map((q, qIndex) => {
      const question = typeof q.question === 'string' ? q.question.trim() : '';
      if (!question) {
        return null;
      }

      const answers = Array.isArray(q.answers)
        ? q.answers
            .map((a, aIndex) => {
              const text = typeof a.text === 'string' ? a.text.trim() : '';
              if (!text) {
                return null;
              }
              return {
                text,
                isCorrect: Boolean(a.is_correct),
                index: aIndex,
              };
            })
            .filter(Boolean)
        : [];

      if (answers.length < 2) {
        return null;
      }

      return {
        question,
        answers,
        correctIndexes: answers.filter((a) => a.isCorrect).map((a) => a.index),
        qIndex,
      };
    })
    .filter(Boolean);

const renderEmpty = (target, message) => {
  target.innerHTML = `<p class="text-sm">${message}</p>`;
};

const renderQuestion = (target, state) => {
  const { questions, currentIndex, selected } = state;
  const question = questions[currentIndex];
  const total = questions.length;
  const selectedSet = new Set(selected);
  const progressPercent = Math.round((currentIndex / total) * 100);

  const dots = questions
    .map(
      (_q, idx) => `
      <button
        type="button"
        class="quiz-dot ${idx === currentIndex ? 'is-active' : ''}"
        data-quiz-dot="${idx}"
        aria-label="Ir a la pregunta ${idx + 1}"
      ></button>`
    )
    .join('');

  const options = question.answers
    .map(
      (answer) => `
      <label class="flex items-center gap-3 rounded border border-white/10 bg-white/5 px-4 py-3">
        <input
          type="checkbox"
          class="quiz-option h-4 w-4 rounded border-white/30 bg-white/10 text-morado1 focus:ring-morado1"
          data-answer-index="${answer.index}"
          ${selectedSet.has(answer.index) ? 'checked' : ''}
        />
        <span class="text-white">${answer.text}</span>
      </label>
    `
    )
    .join('');

  target.innerHTML = `
    <div class="flex items-center justify-between text-sm text-morado1">
      <span>Pregunta ${currentIndex + 1} de ${total}</span>
    </div>
    <div class="mt-2 quiz-progress" aria-hidden="true">
      <div class="quiz-progress-bar" style="--percent: ${progressPercent}%"></div>
    </div>
    <h3 class="mt-3 font-display text-xl font-semibold text-white">${question.question}</h3>
    <div class="mt-4 space-y-3" data-quiz-options>
      ${options}
    </div>
    <div class="mt-5 flex flex-wrap gap-3">
      <button type="button" class="quiz-submit rounded-sm bg-morado1 px-4 py-2 text-sm font-semibold text-morado5 hover:bg-morado2 focus:outline-none focus:ring-2 focus:ring-white/40">
        ${currentIndex + 1 === total ? 'Finalizar' : 'Comprobar y siguiente'}
      </button>
      <button type="button" class="quiz-restart rounded-sm border border-white/20 px-4 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
        Reiniciar cuestionario
      </button>
    </div>
    <div class="mt-3 text-sm text-morado1" data-quiz-feedback></div>
    <div class="quiz-nav">
      <div class="flex items-center gap-2">
        <button type="button" class="quiz-prev rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20" ${currentIndex === 0 ? 'disabled' : ''}>
          ← Anterior
        </button>
        <button type="button" class="quiz-next rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20" ${currentIndex + 1 >= total ? 'disabled' : ''}>
          Siguiente →
        </button>
      </div>
      <div class="quiz-dots" role="tablist" aria-label="Navegación del cuestionario">
        ${dots}
      </div>
    </div>
  `;
};

const renderSummary = (target, state, saveStatus) => {
  const { questions, answers } = state;
  const total = questions.length;
  const correct = answers.filter((a) => a.isCorrect).length;
  const percent = total > 0 ? Math.round((correct / total) * 100) : 0;

  const rows = questions
    .map((q, idx) => {
      const answer = answers.find((a) => a.questionIndex === idx);
      const isCorrect = answer?.isCorrect;
      return `
        <li class="rounded-sm border border-white/10 bg-white/5 px-4 py-3">
          <div class="flex items-center justify-between gap-3">
            <p class="font-semibold text-white">${q.question}</p>
            <span class="text-sm ${isCorrect ? 'text-emerald-300' : 'text-rose-300'}">
              ${isCorrect ? 'Correcto' : 'Incorrecto'}
            </span>
          </div>
        </li>
      `;
    })
    .join('');

  const saveMessage = (() => {
    if (!saveStatus) {
      return '';
    }
    if (saveStatus.state === 'saved') {
      return `<p class="text-sm text-emerald-300">Resultado guardado.</p>`;
    }
    if (saveStatus.state === 'unauthorized') {
      return `<p class="text-sm text-amber-200">Inicia sesión para guardar tu resultado.</p>`;
    }
    if (saveStatus.state === 'error') {
      return `<p class="text-sm text-rose-200">No se pudo guardar el resultado: ${saveStatus.message}</p>`;
    }
    return '';
  })();

  target.innerHTML = `
    <div class="flex items-center justify-between text-sm text-morado1">
      <span>Has completado el cuestionario</span>
      <span class="font-semibold text-white">${correct} / ${total} correctas (${percent}%)</span>
    </div>
    <div class="mt-2 quiz-progress" aria-hidden="true">
      <div class="quiz-progress-bar" style="--percent: ${percent}%"></div>
    </div>
    <ul class="mt-4 space-y-3">
      ${rows}
    </ul>
    <div class="mt-5 flex flex-wrap gap-3">
      <button type="button" class="quiz-restart rounded-sm border border-white/20 px-4 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
        Rehacer cuestionario
      </button>
    </div>
    <div class="mt-3">${saveMessage}</div>
  `;
};

const evaluateSelection = (question, selected) => {
  if (!Array.isArray(selected) || selected.length === 0) {
    return { valid: false, message: 'Selecciona al menos una opción.' };
  }

  const correctSet = new Set(question.correctIndexes);
  const selectedSet = new Set(selected);

  if (correctSet.size === 0) {
    return { valid: false, message: 'La pregunta no tiene respuestas correctas configuradas.' };
  }

  const isCorrect =
    correctSet.size === selectedSet.size &&
    [...correctSet].every((idx) => selectedSet.has(idx));

  return { valid: true, isCorrect };
};

const saveResult = async (postId, answers) => {
  const api = getApiConfig();

  if (!api) {
    return { state: 'noop', message: 'WP API no disponible' };
  }

  try {
    const response = await fetch(`${api.root}cde/v1/quiz/submit`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': api.nonce,
      },
      body: JSON.stringify({
        post_id: postId,
        answers: answers.map((a) => ({
          question_index: a.questionIndex,
          selected: a.selected,
        })),
      }),
    });

    if (response.status === 401) {
      return { state: 'unauthorized', message: 'Inicia sesión para guardar.' };
    }

    if (!response.ok) {
      const data = await response.json().catch(() => ({}));
      return { state: 'error', message: data?.message || 'Error desconocido' };
    }

    return { state: 'saved' };
  } catch (error) {
    return { state: 'error', message: error.message || 'Error de red' };
  }
};

const fetchResult = async (postId) => {
  const api = getApiConfig();

  if (!api) {
    return null;
  }

  try {
    const response = await fetch(`${api.root}cde/v1/quiz/result?post_id=${postId}`, {
      headers: {
        'X-WP-Nonce': api.nonce,
      },
      credentials: 'same-origin',
    });

    if (response.status === 401) {
      return null;
    }

    if (!response.ok) {
      return null;
    }

    const data = await response.json();
    return data?.result || null;
  } catch (e) {
    return null;
  }
};

const initLessonQuiz = () => {
  const container = document.getElementById('lesson-quiz');
  if (!container) {
    return;
  }

  ensureQuizStyles();

  const target = container.querySelector('[data-quiz-target]');
  const props = parseQuizProps(container);
  const questions = normalizeQuestions(props.questions);
  const postId = props.postId;

  if (!postId || !Array.isArray(questions) || questions.length === 0) {
    renderEmpty(target, 'No hay cuestionario disponible.');
    return;
  }

  const state = {
    questions,
    postId,
    currentIndex: 0,
    selected: [],
    selections: {},
    answers: [],
    finished: false,
    feedback: '',
    saveStatus: null,
  };

  const updateFeedback = (message, tone = 'neutral') => {
    const feedbackEl = container.querySelector('[data-quiz-feedback]');
    if (!feedbackEl) return;

    const toneClass =
      tone === 'error'
        ? 'text-rose-200'
        : tone === 'success'
        ? 'text-emerald-300'
        : 'text-morado1';

    feedbackEl.className = `mt-3 text-sm ${toneClass}`;
    feedbackEl.textContent = message;
  };

  const renderWithSlide = (renderer) => {
    renderer();
    gsap.fromTo(
      target,
      { opacity: 0, x: 12 },
      { opacity: 1, x: 0, duration: 0.22, ease: 'power2.out' }
    );
  };

  const reset = () => {
    state.currentIndex = 0;
    state.selected = [];
    state.selections = {};
    state.answers = [];
    state.finished = false;
    state.saveStatus = null;
    renderWithSlide(() => renderQuestion(target, state));
  };

  const renderFromSaved = (saved) => {
    if (!saved || !Array.isArray(saved.answers)) {
      reset();
      return;
    }

    state.answers = saved.answers.map((item, idx) => {
      const selected = Array.isArray(item.selected) ? item.selected : [];
      state.selections[idx] = selected;

      return {
        questionIndex: idx,
        selected,
        isCorrect: Boolean(item.correct),
      };
    });
    state.finished = true;
    state.saveStatus = { state: 'saved' };

    renderWithSlide(() => renderSummary(target, state, state.saveStatus));
  };

  fetchResult(postId).then((saved) => {
    if (saved) {
      renderFromSaved(saved);
    } else {
      reset();
    }
  });

  container.addEventListener('click', async (event) => {
    const submitBtn = event.target.closest('.quiz-submit');
    const restartBtn = event.target.closest('.quiz-restart');
    const prevBtn = event.target.closest('.quiz-prev');
    const nextBtn = event.target.closest('.quiz-next');
    const dotBtn = event.target.closest('.quiz-dot');

    if (submitBtn) {
      const question = state.questions[state.currentIndex];
      const optionsWrapper = container.querySelector('[data-quiz-options]');
      if (!optionsWrapper) return;

      const selected = Array.from(optionsWrapper.querySelectorAll('.quiz-option:checked')).map(
        (el) => Number(el.dataset.answerIndex)
      );

      const evaluation = evaluateSelection(question, selected);
      if (!evaluation.valid) {
        updateFeedback(evaluation.message, 'error');
        return;
      }

      state.answers = [
        ...state.answers.filter((a) => a.questionIndex !== state.currentIndex),
        {
          questionIndex: state.currentIndex,
          selected,
          isCorrect: evaluation.isCorrect,
        },
      ];

      const isLast = state.currentIndex + 1 === state.questions.length;

      if (isLast) {
        state.finished = true;
        const saveStatus = await saveResult(state.postId, state.answers);
        state.saveStatus = saveStatus;
        renderWithSlide(() => renderSummary(target, state, saveStatus));
      } else {
        state.selections[state.currentIndex] = selected;
        state.currentIndex += 1;
        state.selected = state.selections[state.currentIndex] || [];
        renderWithSlide(() => renderQuestion(target, state));
      }
    }

    if (restartBtn) {
      event.preventDefault();
      reset();
      updateFeedback('');
    }

    if (prevBtn) {
      event.preventDefault();
      if (state.currentIndex === 0 || state.finished) return;
      state.selections[state.currentIndex] = state.selected;
      state.currentIndex = Math.max(0, state.currentIndex - 1);
      state.selected = state.selections[state.currentIndex] || [];
      renderWithSlide(() => renderQuestion(target, state));
    }

    if (nextBtn) {
      event.preventDefault();
      if (state.finished) return;
      state.selections[state.currentIndex] = state.selected;
      state.currentIndex = Math.min(state.questions.length - 1, state.currentIndex + 1);
      state.selected = state.selections[state.currentIndex] || [];
      renderWithSlide(() => renderQuestion(target, state));
    }

    if (dotBtn) {
      event.preventDefault();
      if (state.finished) return;
      const targetIndex = Number(dotBtn.dataset.quizDot);
      if (Number.isNaN(targetIndex)) return;
      state.selections[state.currentIndex] = state.selected;
      state.currentIndex = Math.max(0, Math.min(state.questions.length - 1, targetIndex));
      state.selected = state.selections[state.currentIndex] || [];
      renderWithSlide(() => renderQuestion(target, state));
    }
  });

  container.addEventListener('change', (event) => {
    const option = event.target.closest('.quiz-option');
    if (!option) return;

    const idx = Number(option.dataset.answerIndex);
    if (Number.isNaN(idx)) return;

    const selected = new Set(state.selected);
    if (option.checked) {
      selected.add(idx);
    } else {
      selected.delete(idx);
    }

    state.selected = Array.from(selected);
    state.selections[state.currentIndex] = state.selected;
  });

  document.addEventListener('keydown', (event) => {
    if (!container.contains(document.activeElement)) {
      return;
    }
    if (state.finished) return;
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      if (state.currentIndex > 0) {
        state.selections[state.currentIndex] = state.selected;
        state.currentIndex -= 1;
        state.selected = state.selections[state.currentIndex] || [];
        renderWithSlide(() => renderQuestion(target, state));
      }
    }
    if (event.key === 'ArrowRight') {
      event.preventDefault();
      if (state.currentIndex < state.questions.length - 1) {
        state.selections[state.currentIndex] = state.selected;
        state.currentIndex += 1;
        state.selected = state.selections[state.currentIndex] || [];
        renderWithSlide(() => renderQuestion(target, state));
      }
    }
  });
};

export default initLessonQuiz;
