import { Head } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import '../../css/BallBeam.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations';
import {
    CartesianGrid,
    Line,
    LineChart,
    ReferenceLine,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';


export default function BallBeam() {
    const playbackSpeed = 0.3;

    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );

    const [params, setParams] = useState({
        initialPosition: '-0.2',
        targetPosition: '0',
        duration: '5',
    });
    const [simulationData, setSimulationData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [playbackTime, setPlaybackTime] = useState(0);
    const [isPlaying, setIsPlaying] = useState(false);
    const animationFrameRef = useRef(null);

    const t = translations[language].ballBeam;

    useEffect(() => {
        function handleLanguageChange(event) {
            setLanguage(event.detail);
        }

        window.addEventListener('language-change', handleLanguageChange);

        return () => {
            window.removeEventListener('language-change', handleLanguageChange);
        };
    }, []);

    useEffect(() => {
        if (!isPlaying || !simulationData?.time?.length) {
            return;
        }

        const simulationEndTime = simulationData.time[simulationData.time.length - 1];
        const playbackStartedAt = performance.now() - (playbackTime / playbackSpeed) * 1000;

        function updatePlayback(now) {
            const nextPlaybackTime = Math.min(
                ((now - playbackStartedAt) / 1000) * playbackSpeed,
                simulationEndTime
            );

            setPlaybackTime(nextPlaybackTime);

            if (nextPlaybackTime >= simulationEndTime) {
                setIsPlaying(false);
                return;
            }

            animationFrameRef.current = requestAnimationFrame(updatePlayback);
        }

        animationFrameRef.current = requestAnimationFrame(updatePlayback);

        return () => {
            if (animationFrameRef.current) {
                cancelAnimationFrame(animationFrameRef.current);
            }
        };
    }, [isPlaying, simulationData]);

    function updateParam(name, value) {
        const normalizedValue = ['initialPosition', 'targetPosition'].includes(name)
            ? normalizePositionValue(value)
            : value;

        setParams((current) => ({
            ...current,
            [name]: normalizedValue,
        }));

        if (!isPlaying) {
            setSimulationData(null);
            setPlaybackTime(0);
        }
    }

    function normalizePositionValue(value) {
        if (['', '-', '.', '-.'].includes(value)) {
            return value;
        }

        const numericValue = Number(value);

        if (!Number.isFinite(numericValue)) {
            return value;
        }

        if (numericValue < -0.5) {
            return '-0.5';
        }

        if (numericValue > 0.5) {
            return '0.5';
        }

        return value;
    }

    async function runSimulation() {
        setLoading(true);
        setError('');
        setSimulationData(null);
        setPlaybackTime(0);
        setIsPlaying(false);

        try {
            const response = await fetch('/api/simulations/ball-beam', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-API-Key': import.meta.env.VITE_CAS_API_KEY,
                },
                body: JSON.stringify({
                    initial_position: Number(params.initialPosition),
                    initial_velocity: 0,
                    initial_angle: 0,
                    target_position: Number(params.targetPosition),
                    duration: Number(params.duration),
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.error || data.message || t.simulationFailed);
                return;
            }

            setSimulationData(data);
            setPlaybackTime(0);
            setIsPlaying(true);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : t.simulationFailed);
        } finally {
            setLoading(false);
        }
    }

    function togglePlayback() {
        if (!simulationData) {
            return;
        }

        setIsPlaying((current) => !current);
    }

    function restartPlayback() {
        if (!simulationData) {
            return;
        }

        setPlaybackTime(0);
        setIsPlaying(true);
    }

    const pointCount = simulationData?.time?.length || 0;
    const startPosition = pointCount > 0 ? simulationData.position[0] : null;
    const endPosition = pointCount > 0 ? simulationData.position[pointCount - 1] : null;
    const previewPosition = Number.isFinite(Number(params.initialPosition))
        ? Number(params.initialPosition)
        : 0;
    const previewAngle = 0;
    const currentTime = simulationData ? playbackTime : 0;
    const nextFrameAfterCurrentTime = simulationData
        ? simulationData.time.findIndex((time) => time > currentTime)
        : -1;
    const currentFrame = simulationData
        ? nextFrameAfterCurrentTime === -1
            ? pointCount - 1
            : Math.max(0, nextFrameAfterCurrentTime - 1)
        : 0;
    const nextFrame = simulationData
        ? Math.min(currentFrame + 1, pointCount - 1)
        : 0;
    const frameStartTime = simulationData?.time?.[currentFrame] ?? 0;
    const frameEndTime = simulationData?.time?.[nextFrame] ?? frameStartTime;
    const frameProgress = frameEndTime > frameStartTime
        ? (currentTime - frameStartTime) / (frameEndTime - frameStartTime)
        : 0;
    const currentPosition = simulationData
        ? simulationData.position[currentFrame]
            + (simulationData.position[nextFrame] - simulationData.position[currentFrame]) * frameProgress
        : previewPosition;
    const currentAngle = simulationData
        ? simulationData.angle[currentFrame]
            + (simulationData.angle[nextFrame] - simulationData.angle[currentFrame]) * frameProgress
        : previewAngle;
    const currentAngleDegrees = currentAngle * (180 / Math.PI);
    const beamMin = -0.5;
    const beamMax = 0.5;
    const beamEdgeInsetPercent = 14;
    const ballPercent = beamEdgeInsetPercent
        + ((currentPosition - beamMin) / (beamMax - beamMin)) * (100 - beamEdgeInsetPercent * 2);
    const visualBallPosition = Math.max(-20, Math.min(120, ballPercent));

    const chartData = simulationData
        ? simulationData.time.slice(0, currentFrame + 1).map((time, index) => ({
            time,
            position: simulationData.position[index],
            angle: simulationData.angle[index],
        })).concat(
            nextFrame > currentFrame && currentTime > frameStartTime
                ? [{
                    time: currentTime,
                    position: currentPosition,
                    angle: currentAngle,
                }]
                : []
        )
        : [];
    const chartEndTime = simulationData?.time?.[pointCount - 1] ?? 0;
    const positionValues = simulationData?.position ?? [];
    const minPosition = positionValues.length ? Math.min(...positionValues) : -0.5;
    const maxPosition = positionValues.length ? Math.max(...positionValues) : 0.5;
    const positionPadding = Math.max((maxPosition - minPosition) * 0.1, 0.05);


    return (
        <AppLayout>
            <Head title={t.pageTitle} />

            <section className="simulation-page">
                <div className="simulation-header">
                    <h1>{t.pageTitle}</h1>
                    <p>{t.intro}</p>
                </div>

                <div className="simulation-layout">
                    <form className="simulation-form">
                        <label>
                            <span>{t.initialPosition}</span>
                            <input
                                type="number"
                                min="-0.5"
                                max="0.5"
                                step="0.01"
                                value={params.initialPosition}
                                disabled={loading || isPlaying}
                                onChange={(event) => updateParam('initialPosition', event.target.value)}
                            />
                            <small>{t.positionRangeHint}</small>
                        </label>

                        <label>
                            <span>{t.targetPosition}</span>
                            <input
                                type="number"
                                min="-0.5"
                                max="0.5"
                                step="0.01"
                                value={params.targetPosition}
                                disabled={loading || isPlaying}
                                onChange={(event) => updateParam('targetPosition', event.target.value)}
                            />
                            <small>{t.positionRangeHint}</small>
                        </label>

                        <label>
                            <span>{t.duration}</span>
                            <input
                                type="number"
                                value={params.duration}
                                disabled={loading || isPlaying}
                                onChange={(event) => updateParam('duration', event.target.value)}
                            />
                        </label>

                        <button type="button" onClick={runSimulation} disabled={loading || isPlaying}>
                            {loading ? t.running : t.runSimulation}
                        </button>
                    </form>

                    <div className="simulation-results">
                        <section className="simulation-panel simulation-animation-panel">
                            <h2>{t.animation}</h2>
                            <div className="ball-beam-preview">
                                <div
                                    className="beam-line"
                                    style={{ transform: `rotate(${currentAngleDegrees}deg)` }}
                                >
                                    <div
                                        className="beam-ball"
                                        style={{ left: `${visualBallPosition}%` }}
                                    />
                                </div>
                            </div>

                            {simulationData && (
                                <div className="simulation-live-values">
                                    <span>{t.currentTime}: {currentTime.toFixed(2)} s</span>
                                    <span>{t.currentPosition}: {currentPosition.toFixed(4)} m</span>
                                    <span>{t.currentAngle}: {currentAngleDegrees.toFixed(2)}°</span>
                                </div>
                            )}

                            <div className="simulation-controls">
                                <button
                                    type="button"
                                    onClick={togglePlayback}
                                    disabled={!simulationData}
                                >
                                    {isPlaying ? t.pause : t.play}
                                </button>

                                <button
                                    type="button"
                                    onClick={restartPlayback}
                                    disabled={!simulationData}
                                >
                                    {t.restart}
                                </button>
                            </div>

                            {error && (
                                <p className="simulation-error">{error}</p>
                            )}

                            {simulationData && (
                                <div className="simulation-summary">
                                    <h3>{t.dataSummary}</h3>
                                    <dl>
                                        <div>
                                            <dt>{t.dataPoints}</dt>
                                            <dd>{pointCount}</dd>
                                        </div>
                                        <div>
                                            <dt>{t.startPosition}</dt>
                                            <dd>{startPosition?.toFixed(4)}</dd>
                                        </div>
                                        <div>
                                            <dt>{t.endPosition}</dt>
                                            <dd>{endPosition?.toFixed(4)}</dd>
                                        </div>
                                    </dl>
                                </div>
                            )}
                        </section>

                    </div>

                    <section className="simulation-panel simulation-graph-panel">
                        <h2>{t.graph}</h2>
                        <ResponsiveContainer width="100%" height={300}>
                            <LineChart data={chartData} margin={{ top: 8, right: 24, bottom: 8, left: 28 }}>
                                <CartesianGrid strokeDasharray="3 3" />
                                <XAxis
                                    dataKey="time"
                                    type="number"
                                    domain={[0, chartEndTime]}
                                />
                                <YAxis
                                    width={72}
                                    domain={[minPosition - positionPadding, maxPosition + positionPadding]}
                                    tickFormatter={(value) => Number(value).toFixed(3)}
                                />
                                <Tooltip />
                                <Line
                                    type="monotone"
                                    dataKey="position"
                                    stroke="#57C4CE"
                                    dot={false}
                                    isAnimationActive={false}
                                />
                                {simulationData && (
                                    <ReferenceLine
                                        x={currentTime}
                                        stroke="#57C4CE"
                                        strokeWidth={2}
                                    />
                                )}
                            </LineChart>
                        </ResponsiveContainer>
                    </section>
                </div>
            </section>
        </AppLayout>
    );
}
