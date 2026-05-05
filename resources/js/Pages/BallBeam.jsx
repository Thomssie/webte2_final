import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
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
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );

    const [params, setParams] = useState({
        initialPosition: '-0.2',
        initialVelocity: '0',
        initialAngle: '0',
        targetPosition: '0',
        duration: '8',
    });
    const [simulationData, setSimulationData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [currentFrame, setCurrentFrame] = useState(0);
    const [isPlaying, setIsPlaying] = useState(false);

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

        const interval = setInterval(() => {
            setCurrentFrame((frame) => {
                if (frame >= simulationData.time.length - 1) {
                    clearInterval(interval);
                    setIsPlaying(false);
                    return frame;
                }

                return frame + 1;
            });
        }, 50);

        return () => clearInterval(interval);
    }, [isPlaying, simulationData]);

    function updateParam(name, value) {
        setParams((current) => ({
            ...current,
            [name]: value,
        }));
    }

    async function runSimulation() {
        setLoading(true);
        setError('');
        setSimulationData(null);
        setCurrentFrame(0);
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
                    initial_velocity: Number(params.initialVelocity),
                    initial_angle: Number(params.initialAngle),
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
            setCurrentFrame(0);
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

        setCurrentFrame(0);
        setIsPlaying(true);
    }

    const pointCount = simulationData?.time?.length || 0;
    const startPosition = pointCount > 0 ? simulationData.position[0] : null;
    const endPosition = pointCount > 0 ? simulationData.position[pointCount - 1] : null;
    const currentPosition = simulationData?.position?.[currentFrame] ?? 0;
    const currentAngle = simulationData?.angle?.[currentFrame] ?? 0;
    const currentAngleDegrees = currentAngle * (180 / Math.PI);
    const currentTime = simulationData?.time?.[currentFrame] ?? 0;
    const beamMin = -0.5;
    const beamMax = 0.5;
    const ballPercent = ((currentPosition - beamMin) / (beamMax - beamMin)) * 100;
    const normalizedBallPosition = Math.max(5, Math.min(95, ballPercent));

    const chartData = simulationData
        ? simulationData.time.map((time, index) => ({
            time,
            position: simulationData.position[index],
            angle: simulationData.angle[index],
        }))
        : [];


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
                                value={params.initialPosition}
                                onChange={(event) => updateParam('initialPosition', event.target.value)}
                            />
                        </label>

                        <label>
                            <span>{t.initialVelocity}</span>
                            <input
                                type="number"
                                value={params.initialVelocity}
                                onChange={(event) => updateParam('initialVelocity', event.target.value)}
                            />
                        </label>

                        <label>
                            <span>{t.initialAngle}</span>
                            <input
                                type="number"
                                value={params.initialAngle}
                                onChange={(event) => updateParam('initialAngle', event.target.value)}
                            />
                        </label>

                        <label>
                            <span>{t.targetPosition}</span>
                            <input
                                type="number"
                                step="0.01"
                                value={params.targetPosition}
                                onChange={(event) => updateParam('targetPosition', event.target.value)}
                            />
                        </label>

                        <label>
                            <span>{t.duration}</span>
                            <input
                                type="number"
                                value={params.duration}
                                onChange={(event) => updateParam('duration', event.target.value)}
                            />
                        </label>

                        <button type="button" onClick={runSimulation} disabled={loading}>
                            {loading ? t.running : t.runSimulation}
                        </button>
                    </form>

                    <div className="simulation-results">
                        <section className="simulation-panel">
                            <h2>{t.animation}</h2>
                            <div className="ball-beam-preview">
                                <div
                                    className="beam-line"
                                    style={{ transform: `rotate(${currentAngleDegrees}deg)` }}
                                >
                                    <div
                                        className="beam-ball"
                                        style={{ left: `${normalizedBallPosition}%` }}
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

                        <section className="simulation-panel">

                            <h2>{t.graph}</h2>
                            <ResponsiveContainer width="100%" height={240}>
                                <LineChart data={chartData}>
                                    <CartesianGrid strokeDasharray="3 3" />
                                    <XAxis dataKey="time" />
                                    <YAxis />
                                    <Tooltip />
                                    <Line type="monotone" dataKey="position" stroke="#57C4CE" dot={false} />
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
                </div>
            </section>
        </AppLayout>
    );
}
