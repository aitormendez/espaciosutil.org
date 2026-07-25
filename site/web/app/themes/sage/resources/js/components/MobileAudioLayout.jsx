import React from 'react';
import {
  ChapterTitle,
  Menu,
  PlayButton,
  SeekButton,
  Time,
  TimeSlider,
  Title,
  useChapterOptions,
  useMediaState,
  usePlaybackRateOptions,
} from '@vidstack/react';
import { defaultLayoutIcons } from '@vidstack/react/player/layouts/default';

const PLAYBACK_RATES = [0.75, 1, 1.25, 1.5, 2];

const MobileAudioTimeSlider = () => (
  <TimeSlider.Root
    className="vds-time-slider vds-slider featured-audio-mobile-slider"
    aria-label="Progreso de reproducción"
  >
    <TimeSlider.Chapters className="vds-slider-chapters">
      {(cues, forwardRef) =>
        cues.map((cue) => (
          <div
            className="vds-slider-chapter"
            key={cue.startTime}
            ref={forwardRef}
          >
            <TimeSlider.Track className="vds-slider-track" />
            <TimeSlider.TrackFill className="vds-slider-track-fill vds-slider-track" />
            <TimeSlider.Progress className="vds-slider-progress vds-slider-track" />
          </div>
        ))
      }
    </TimeSlider.Chapters>
    <TimeSlider.Thumb className="vds-slider-thumb" />
    <TimeSlider.Preview className="vds-slider-preview">
      <TimeSlider.ChapterTitle className="vds-slider-chapter-title" />
      <TimeSlider.Value className="vds-slider-value" />
    </TimeSlider.Preview>
  </TimeSlider.Root>
);

const MobileChaptersMenu = () => {
  const options = useChapterOptions();
  const ChaptersIcon = defaultLayoutIcons.Menu.Chapters;

  return (
    <Menu.Root className="vds-chapters-menu vds-menu">
      <Menu.Button
        className="vds-menu-button featured-audio-mobile-utility-button"
        disabled={!options.length}
        aria-label="Capítulos"
      >
        <ChaptersIcon className="vds-icon" />
      </Menu.Button>
      <Menu.Items
        className="vds-chapters-menu-items vds-menu-items featured-audio-mobile-menu-items"
        placement="top end"
        offset={8}
      >
        <div className="featured-audio-mobile-menu-heading">Capítulos</div>
        <Menu.RadioGroup
          className="vds-chapters-radio-group vds-radio-group"
          value={options.selectedValue}
        >
          {options.map(
            ({
              label,
              value,
              startTimeText,
              durationText,
              select,
              setProgressVar,
            }) => (
              <Menu.Radio
                className="vds-chapter-radio vds-radio"
                value={value}
                key={value}
                onSelect={select}
                ref={setProgressVar}
              >
                <span className="vds-chapter-radio-content">
                  <span className="vds-chapter-radio-label">{label}</span>
                  <span className="vds-chapter-radio-start-time">
                    {startTimeText}
                  </span>
                  <span className="vds-chapter-radio-duration">
                    {durationText}
                  </span>
                </span>
              </Menu.Radio>
            )
          )}
        </Menu.RadioGroup>
      </Menu.Items>
    </Menu.Root>
  );
};

const MobileSettingsMenu = () => {
  const options = usePlaybackRateOptions({
    rates: PLAYBACK_RATES,
    normalLabel: 'Normal',
  });
  const SettingsIcon = defaultLayoutIcons.Menu.Settings;

  return (
    <Menu.Root className="vds-settings-menu vds-menu">
      <Menu.Button
        className="vds-menu-button featured-audio-mobile-utility-button"
        disabled={options.disabled}
        aria-label="Velocidad de reproducción"
      >
        <SettingsIcon className="vds-icon" />
      </Menu.Button>
      <Menu.Items
        className="vds-menu-items featured-audio-mobile-menu-items"
        placement="top end"
        offset={8}
      >
        <div className="featured-audio-mobile-menu-heading">
          Velocidad de reproducción
        </div>
        <Menu.RadioGroup
          className="vds-radio-group featured-audio-mobile-rate-options"
          value={options.selectedValue}
        >
          {options.map(({ label, value, selected, select }) => (
            <Menu.Radio
              className="vds-radio featured-audio-mobile-rate-option"
              value={value}
              key={value}
              onSelect={select}
            >
              <span>{label}</span>
              <span aria-hidden="true">{selected ? '✓' : ''}</span>
            </Menu.Radio>
          ))}
        </Menu.RadioGroup>
      </Menu.Items>
    </Menu.Root>
  );
};

const MobileAudioLayout = () => {
  const paused = useMediaState('paused');
  const PlayStateIcon = paused
    ? defaultLayoutIcons.PlayButton.Play
    : defaultLayoutIcons.PlayButton.Pause;
  const SeekBackwardIcon = defaultLayoutIcons.SeekButton.Backward;
  const SeekForwardIcon = defaultLayoutIcons.SeekButton.Forward;

  return (
    <section
      className="featured-audio-mobile-layout dark"
      aria-label="Reproductor de audio"
    >
      <div
        className="featured-audio-mobile-title"
        data-audio-section="title"
      >
        <Title />
        <ChapterTitle className="featured-audio-mobile-chapter-title" />
      </div>

      <div
        className="featured-audio-mobile-primary-controls"
        data-audio-section="controls"
      >
        <SeekButton
          className="featured-audio-mobile-control-button"
          seconds={-10}
          aria-label="Retroceder 10 segundos"
        >
          <SeekBackwardIcon className="vds-icon" />
        </SeekButton>
        <PlayButton
          className="featured-audio-mobile-control-button featured-audio-mobile-play-button"
          aria-label={paused ? 'Reproducir' : 'Pausar'}
        >
          <PlayStateIcon className="vds-icon" />
        </PlayButton>
        <SeekButton
          className="featured-audio-mobile-control-button"
          seconds={10}
          aria-label="Avanzar 10 segundos"
        >
          <SeekForwardIcon className="vds-icon" />
        </SeekButton>
      </div>

      <div
        className="featured-audio-mobile-progress"
        data-audio-section="progress"
      >
        <MobileAudioTimeSlider />
      </div>

      <div
        className="featured-audio-mobile-utilities"
        data-audio-section="utilities"
      >
        <div className="featured-audio-mobile-time">
          <span>Restante</span>
          <Time type="current" toggle remainder />
        </div>
        <div className="featured-audio-mobile-menus">
          <MobileChaptersMenu />
          <MobileSettingsMenu />
        </div>
      </div>
    </section>
  );
};

export default MobileAudioLayout;
