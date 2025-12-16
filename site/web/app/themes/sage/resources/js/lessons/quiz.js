import Swiper from 'swiper';
import { Navigation, Pagination, Keyboard } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const ensureQuizStyles = (() => {
  let injected = false;

  return () => {
    if (injected) return;
    injected = true;

    const style = document.createElement('style');
    style.textContent = `
      #lesson-quiz [data-quiz-target] {
        position: relative;
        overflow: hidden;
      }
      #lesson-quiz .quiz-progress {
        position: relative;
        height: 8px;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        overflow: hidden;
        margin: 12px 0 16px;
      }
      :root {
        --quiz-score-width: 848px;
      }
      #lesson-quiz .quiz-progress-bar {
        height: 100%;
        width: var(--percent, 0%);
        background: linear-gradient(90deg, #a855f7, #67e8f9);
        transition: width 240ms ease;
      }
      #lesson-quiz .quiz-progress-bar.question {
        background: var(--quiz-question-color, #fbbf24); /* bg-sun */
      }
      #lesson-quiz .quiz-progress-bar.score {
        background: none;
        position: relative;
        overflow: hidden;
      }
      #lesson-quiz .quiz-progress-bar.score::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        width: var(--quiz-score-width, 848px);
        background: linear-gradient(90deg, #a855f7, #ffffff);
        background-repeat: repeat-x;
        pointer-events: none;
      }
      #lesson-quiz .quiz-shell {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      #lesson-quiz .quiz-swiper {
        width: 100%;
      }
      #lesson-quiz .quiz-swiper .swiper-wrapper {
        display: flex;
        transition-property: transform;
        box-sizing: content-box;
      }
      #lesson-quiz .quiz-swiper .swiper-slide {
        width: 100%;
        flex-shrink: 0;
        box-sizing: border-box;
      }
      #lesson-quiz .quiz-slide {
        padding: 4px 0 12px;
      }
      #lesson-quiz .quiz-options {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 16px;
      }
      #lesson-quiz .quiz-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 6px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.05);
      }
      #lesson-quiz .quiz-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 12px;
      }
      #lesson-quiz .quiz-pagination {
        position: static;
      }
      #lesson-quiz .quiz-pagination .swiper-pagination-bullet {
        background: rgba(255,255,255,0.4);
        opacity: 1;
      }
      #lesson-quiz .quiz-pagination .swiper-pagination-bullet-active {
        background: linear-gradient(90deg, #a855f7, #67e8f9);
      }
      #lesson-quiz .quiz-summary {
        padding: 4px 0;
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

const buildSlidesHtml = (questions, selections) =>
  questions
    .map(
      (q, idx) => `
    <div class="swiper-slide quiz-slide" data-question-index="${idx}">
      <div class="text-sm text-morado1 mb-2">Pregunta ${idx + 1} de ${
        questions.length
      }</div>
      <h3 class="font-display text-xl font-semibold text-white">${
        q.question
      }</h3>
      <div class="quiz-options" data-quiz-options>
        ${q.answers
          .map(
            (a) => `
          <label class="quiz-option">
            <input
              type="checkbox"
              class="quiz-checkbox h-4 w-4 rounded border-white/30 bg-white/10 text-morado1 focus:ring-morado1"
              data-answer-index="${a.index}"
              ${selections[idx]?.includes(a.index) ? 'checked' : ''}
              tabindex="0"
            />
            <span class="text-white">${a.text}</span>
          </label>
        `
          )
          .join('')}
      </div>
    </div>`
    )
    .join('');

const renderEmpty = (target, message) => {
  target.innerHTML = `<p class="text-sm">${message}</p>`;
};

const evaluateSelection = (question, selected) => {
  if (!Array.isArray(selected) || selected.length === 0) {
    return { valid: false, message: 'Selecciona al menos una opción.' };
  }

  const correctSet = new Set(question.correctIndexes);
  const selectedSet = new Set(selected);

  if (correctSet.size === 0) {
    return {
      valid: false,
      message: 'La pregunta no tiene respuestas correctas configuradas.',
    };
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
    const response = await fetch(
      `${api.root}cde/v1/quiz/result?post_id=${postId}`,
      {
        headers: {
          'X-WP-Nonce': api.nonce,
        },
        credentials: 'same-origin',
      }
    );

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
            <span class="text-sm ${
              isCorrect ? 'text-emerald-300' : 'text-rose-300'
            }">
              ${isCorrect ? 'Correcto' : 'Incorrecto'}
            </span>
          </div>
        </li>
      `;
    })
    .join('');

  const saveMessage = (() => {
    if (!saveStatus) return '';
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
    <div class="quiz-summary">
      <div class="flex items-center justify-between text-sm text-morado1">
        <span>Has completado el cuestionario</span>
        <span class="font-semibold text-white">${correct} / ${total} correctas (${percent}%)</span>
      </div>
      <div class="mt-2 quiz-progress" aria-hidden="true">
        <div class="quiz-progress-bar score" style="--percent: ${percent}%"></div>
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
    </div>
  `;
};

const updateProgressBar = (root, currentIndex, totalQuestions) => {
  const bar = root.querySelector('.quiz-progress-bar');
  if (!bar) return;
  const divisor = Math.max(totalQuestions - 1, 1);
  const percent = Math.round((currentIndex / divisor) * 100);
  bar.style.setProperty('--percent', `${percent}%`);
  if (bar.classList.contains('score')) {
    const inner = bar.querySelector(':before');
    // El pseudo-elemento usa width fijo; nada que actualizar aquí.
  }
};

const collectSlideSelection = (slide) => {
  const checkboxes = slide.querySelectorAll('[data-answer-index]');
  const selected = [];
  checkboxes.forEach((input) => {
    if (input.checked) {
      selected.push(Number(input.dataset.answerIndex));
    }
  });
  return selected;
};

const applySelectionsToSlide = (slide, selection = []) => {
  const set = new Set(selection);
  slide.querySelectorAll('[data-answer-index]').forEach((input) => {
    const idx = Number(input.dataset.answerIndex);
    input.checked = set.has(idx);
  });
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
    selections: {},
    answers: [],
    finished: false,
    saveStatus: null,
    swiper: null,
  };

  const buildShell = () => {
    target.innerHTML = `
    <div class="quiz-shell">
      <div class="quiz-progress" aria-hidden="true">
        <div class="quiz-progress-bar question" style="--percent: 0%"></div>
      </div>
      <div class="quiz-swiper swiper" role="region" aria-label="Cuestionario">
        <div class="swiper-wrapper">
          ${buildSlidesHtml(state.questions, state.selections)}
        </div>
        <div class="quiz-pagination swiper-pagination"></div>
      </div>
      <div class="quiz-footer">
        <div class="flex items-center gap-2">
          <button type="button" class="quiz-prev rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
            ← Anterior
          </button>
          <button type="button" class="quiz-next rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
            Siguiente →
          </button>
        </div>
        <div class="flex items-center gap-3">
          <button type="button" class="quiz-validate-next rounded-sm bg-morado1 px-4 py-3 text-sm font-semibold text-morado5 hover:bg-morado2 focus:outline-none focus:ring-2 focus:ring-white/40">
            Validar y pasar a la siguiente
          </button>
          <button type="button" class="quiz-submit rounded-sm bg-morado1 px-4 py-2 text-sm font-semibold text-morado5 hover:bg-morado2 focus:outline-none focus:ring-2 focus:ring-white/40">
            Finalizar
          </button>
          <button type="button" class="quiz-restart rounded-sm border border-white/20 px-3 py-2 text-sm text-white hover:border-white/40 focus:outline-none focus:ring-2 focus:ring-white/20">
            Reiniciar
          </button>
        </div>
      </div>
    </div>
  `;
  };

  const initSwiper = () => {
    const swiperEl = target.querySelector('.quiz-swiper');
    if (!swiperEl) return;

    state.swiper = new Swiper(swiperEl, {
      modules: [Navigation, Pagination, Keyboard],
      speed: 260,
      allowTouchMove: false,
      preventClicks: false,
      preventClicksPropagation: false,
      slidesPerView: 1,
      spaceBetween: 12,
      navigation: {
        nextEl: container.querySelector('.quiz-next'),
        prevEl: container.querySelector('.quiz-prev'),
      },
      pagination: {
        el: container.querySelector('.quiz-pagination'),
        clickable: true,
      },
      keyboard: {
        enabled: true,
      },
      on: {
        slideChange: () => {
          const { activeIndex, previousIndex } = state.swiper;
          const prevSlide = state.swiper.slides[previousIndex] || null;
          if (prevSlide) {
            state.selections[previousIndex] = collectSlideSelection(prevSlide);
          }
          updateProgressBar(container, activeIndex, state.questions.length);
          const currentSlide = state.swiper.slides[activeIndex];
          const selection = state.selections[activeIndex] || [];
          applySelectionsToSlide(currentSlide, selection);
          const validateBtn = container.querySelector('.quiz-validate-next');
          if (validateBtn) {
            validateBtn.style.display =
              activeIndex === state.questions.length - 1
                ? 'none'
                : 'inline-block';
          }
          const submitBtn = container.querySelector('.quiz-submit');
          if (submitBtn) {
            submitBtn.disabled = activeIndex !== state.questions.length - 1;
          }
        },
      },
    });

    updateProgressBar(container, 0, state.questions.length);
    const validateBtn = container.querySelector('.quiz-validate-next');
    if (validateBtn && state.questions.length === 1) {
      validateBtn.style.display = 'none';
    }
    const submitBtn = container.querySelector('.quiz-submit');
    if (submitBtn && state.questions.length > 1) {
      submitBtn.disabled = true;
    }
  };

  const collectAllAnswers = () => {
    if (!state.swiper) return;
    const slides = state.swiper.slides;
    slides.forEach((slide, idx) => {
      state.selections[idx] = collectSlideSelection(slide);
    });
  };

  const evaluateAll = () => {
    const answers = [];
    let hasError = null;

    state.questions.forEach((q, idx) => {
      const selected = state.selections[idx] || [];
      const evaluation = evaluateSelection(q, selected);
      if (!evaluation.valid) {
        hasError = `Pregunta ${idx + 1}: ${evaluation.message}`;
        return;
      }
      answers.push({
        questionIndex: idx,
        selected,
        isCorrect: evaluation.isCorrect,
      });
    });

    if (hasError) {
      return { error: hasError };
    }

    state.answers = answers;
    return { ok: true };
  };

  const goToSummary = (saveStatus) => {
    state.finished = true;
    renderSummary(target, state, saveStatus);
  };

  const restart = () => {
    state.finished = false;
    state.selections = {};
    state.answers = [];
    state.saveStatus = null;
    buildShell();
    initSwiper();
  };

  const attachEvents = () => {
    target.addEventListener('click', async (event) => {
      const submitBtn = event.target.closest('.quiz-submit');
      const restartBtn = event.target.closest('.quiz-restart');
      const validateNextBtn = event.target.closest('.quiz-validate-next');

      if (validateNextBtn) {
        if (!state.swiper) return;
        const idx = state.swiper.activeIndex;
        const slide = state.swiper.slides[idx];
        const selected = collectSlideSelection(slide);
        const evaluation = evaluateSelection(state.questions[idx], selected);
        if (!evaluation.valid) {
          alert(evaluation.message);
          return;
        }
        state.selections[idx] = selected;
        if (idx < state.questions.length - 1) {
          state.swiper.slideNext();
        }
        return;
      }

      if (submitBtn) {
        if (state.finished) {
          restart();
          return;
        }

        collectAllAnswers();
        const evaluated = evaluateAll();
        if (evaluated?.error) {
          alert(evaluated.error); // simple feedback; puede sustituirse por UI inline
          return;
        }

        const saveStatus = await saveResult(state.postId, state.answers);
        state.saveStatus = saveStatus;
        goToSummary(saveStatus);
      }

      if (restartBtn) {
        event.preventDefault();
        restart();
      }
    });
  };

  const boot = async () => {
    const saved = await fetchResult(postId);

    if (saved && Array.isArray(saved.answers)) {
      state.answers = saved.answers.map((item, idx) => ({
        questionIndex: idx,
        selected: Array.isArray(item.selected) ? item.selected : [],
        isCorrect: Boolean(item.correct),
      }));
      state.selections = saved.answers.reduce((acc, item, idx) => {
        acc[idx] = Array.isArray(item.selected) ? item.selected : [];
        return acc;
      }, {});
      state.saveStatus = { state: 'saved' };
      goToSummary(state.saveStatus);
      attachEvents();
      return;
    }

    buildShell();
    initSwiper();
    attachEvents();
  };

  boot();
};

export default initLessonQuiz;
