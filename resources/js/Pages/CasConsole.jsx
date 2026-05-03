import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import '../../css/CasConsole.css';


export default function CasConsole() {
    const [command, setCommand] = useState('1+1');
    const [output, setOutput] = useState('');
    const [loading, setLoading] = useState(false);
    const [history, setHistory] = useState([]);


    useEffect(() => {
        if (!localStorage.getItem('cas_session_token')) {
            localStorage.setItem('cas_session_token', crypto.randomUUID());
        }

        loadHistory();
    }, []);


    async function runCommand() {
        setLoading(true);
        setOutput('');

        const sessionToken = localStorage.getItem('cas_session_token') || 'default-session';

        try {
            const response = await fetch('/api/cas/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-API-Key': import.meta.env.VITE_CAS_API_KEY,
                    'X-Session-Token': sessionToken,
                },
                body: JSON.stringify({
                    command,
                    source: 'form',
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setOutput(data.error || data.message || 'Unknown error');
                return;
            }

            setOutput(data.output || '(no output)');
            await loadHistory();
        } catch (error) {
            setOutput(error instanceof Error ? error.message : 'Request failed');
        } finally {
            setLoading(false);
        }
    }

    async function resetWorkspace() {
        setLoading(true);
        setOutput('');

        const sessionToken = localStorage.getItem('cas_session_token') || 'default-session';

        try {
            const response = await fetch('/api/cas/history/reset', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-API-Key': import.meta.env.VITE_CAS_API_KEY,
                    'X-Session-Token': sessionToken,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setOutput(data.error || data.message || 'Reset failed');
                return;
            }

            setOutput(`Workspace reset. Deleted commands: ${data.deleted_count}`);
            setHistory([]);
        } catch (error) {
            setOutput(error instanceof Error ? error.message : 'Reset failed');
        } finally {
            setLoading(false);
        }
    }

    function getSessionToken() {
        return localStorage.getItem('cas_session_token') || 'default-session';
    }


    async function loadHistory() {
        const response = await fetch('/api/cas/history', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-API-Key': import.meta.env.VITE_CAS_API_KEY,
                'X-Session-Token': getSessionToken(),
            },
        });

        const data = await response.json();

        if (response.ok) {
            setHistory(data.commands || []);
        }
    }



    return (
        <>
            <Head title="CAS Console" />

            <main className="cas-console">
                <h1 className="cas-console__title">CAS Console</h1>

                <textarea
                    value={command}
                    onChange={(event) => setCommand(event.target.value)}
                    rows={8}
                    className="cas-console__textarea"
                />

                <div className="cas-console__actions">
                    <button
                        onClick={runCommand}
                        disabled={loading}
                        className="cas-console__button"
                    >
                        {loading ? 'Running...' : 'Run command'}
                    </button>

                    <button
                        onClick={resetWorkspace}
                        disabled={loading}
                        className="cas-console__button"
                    >
                        Reset workspace
                    </button>
                </div>


                <pre className="cas-console__output">
                    {output}
                </pre>

                <section className="cas-console__history">
                    <h2 className="cas-console__history-title">History</h2>

                    {history.length === 0 ? (
                        <p className="cas-console__history-empty">No commands yet.</p>
                    ) : (
                        <ol className="cas-console__history-list">
                            {history.map((item) => (
                                <li key={item.sequence} className="cas-console__history-item">
                                    <code>{item.command}</code>
                                </li>
                            ))}
                        </ol>
                    )}
                </section>
            </main>
        </>
    );

}
