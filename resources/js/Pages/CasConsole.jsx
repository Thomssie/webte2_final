import { Head } from '@inertiajs/react';
import CodeMirror from '@uiw/react-codemirror';
import { useEffect, useState } from 'react';
import '../../css/CasConsole.css';
import AppLayout from '../Layouts/AppLayout';
import translations from '../translations.js';
import { StreamLanguage } from '@codemirror/language';
import { octave } from '@codemirror/legacy-modes/mode/octave';
import { getCsrfToken } from '../csrf.js';
import { appUrl } from '../url.js';



export default function CasConsole() {
    const [command, setCommand] = useState('1+1');
    const [output, setOutput] = useState('');
    const [loading, setLoading] = useState(false);
    const [history, setHistory] = useState([]);
    const [language, setLanguage] = useState(
        localStorage.getItem('app_language') || 'en'
    );

    const t = translations[language].casConsole;


    useEffect(() => {
        if (!localStorage.getItem('cas_session_token')) {
            localStorage.setItem('cas_session_token', crypto.randomUUID());
        }

        loadHistory();

        function handleLanguageChange(event) {
            setLanguage(event.detail);
        }

        window.addEventListener('language-change', handleLanguageChange);

        return () => {
            window.removeEventListener('language-change', handleLanguageChange);
        };
    }, []);

    async function runCommand() {
        setLoading(true);
        setOutput('');

        const sessionToken = localStorage.getItem('cas_session_token') || 'default-session';

        try {
            const response = await fetch(appUrl('/web/cas/execute'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Session-Token': sessionToken,
                },
                body: JSON.stringify({
                    command,
                    source: 'form',
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setOutput(data.error || data.message || t.unknownError);
                return;
            }

            setOutput(data.output || t.noOutput);
            await loadHistory();
        } catch (error) {
            setOutput(error instanceof Error ? error.message : t.requestFailed);
        } finally {
            setLoading(false);
        }
    }

    async function resetWorkspace() {
        setLoading(true);
        setOutput('');

        const sessionToken = localStorage.getItem('cas_session_token') || 'default-session';

        try {
            const response = await fetch(appUrl('/web/cas/history/reset'), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Session-Token': sessionToken,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setOutput(data.error || data.message || t.resetFailed);
                return;
            }

            setOutput(`${t.workspaceReset} ${data.deleted_count}`);
            setHistory([]);
        } catch (error) {
            setOutput(error instanceof Error ? error.message : t.resetFailed);
        } finally {
            setLoading(false);
        }
    }

    function getSessionToken() {
        return localStorage.getItem('cas_session_token') || 'default-session';
    }

    async function loadHistory() {
        const response = await fetch(appUrl('/web/cas/history'), {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Session-Token': getSessionToken(),
            },
        });

        const data = await response.json();

        if (response.ok) {
            setHistory(data.commands || []);
        }
    }

    return (
        <AppLayout>
            <Head title={t.pageTitle} />

            <section className="cas-console">
                <h1 className="cas-title">{t.pageTitle}</h1>

                <div className="cas-editor">
                    <CodeMirror
                        value={command}
                        height="100%"
                        extensions={[StreamLanguage.define(octave)]}
                        basicSetup={{
                            lineNumbers: true,
                            foldGutter: false,
                        }}
                        onChange={(value) => setCommand(value)}
                    />
                </div>

                <div className="cas-actions">
                    <button
                        onClick={runCommand}
                        disabled={loading}
                        className="cas-button"
                    >
                        {loading ? t.running : t.runCommand}
                    </button>

                    <button
                        onClick={resetWorkspace}
                        disabled={loading}
                        className="cas-button"
                    >
                        {t.resetWorkspace}
                    </button>

                </div>


                <pre className="cas-output">
                    {output}
                </pre>

                <section className="cas-history">
                    <h2 className="cas-history-title">{t.history}</h2>

                    {history.length === 0 ? (
                        <p className="cas-history-empty">{t.noCommands}</p>
                    ) : (
                        <ol className="cas-history-list">
                            {history.map((item) => (
                                <li key={item.sequence} className="cas-history-item">
                                    <code>{item.command}</code>
                                </li>
                            ))}
                        </ol>
                    )}
                </section>
            </section>
        </AppLayout>
    );

}
