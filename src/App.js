import React, { useState } from 'react';

const App = () => {
    const [activeTab, setActiveTab] = useState('main');
    const [isRunning, setIsRunning] = useState(false);
    const [log, setLog] = useState('');

    const handleRunScraper = async () => {
        setIsRunning(true);
        setLog('Running scraper...');

        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'run_calendar_scraper',
                    _ajax_nonce: calendarScraperAjax.nonce,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setLog(data.data.message);
            } else {
                setLog(data.data?.message || 'Something went wrong.');
            }
        } catch (error) {
            setLog('Error: ' + error.message);
        } finally {
            setIsRunning(false);
        }
    };

    return (
        <div>
            <h2 className="nav-tab-wrapper">
                <a
                    href="#main"
                    className={`nav-tab ${activeTab === 'main' ? 'nav-tab-active' : ''}`}
                    onClick={(e) => {
                        e.preventDefault();
                        setActiveTab('main');
                    }}
                >
                    Main
                </a>
                <a
                    href="#sheet-colors"
                    className={`nav-tab ${activeTab === 'sheet-colors' ? 'nav-tab-active' : ''}`}
                    onClick={(e) => {
                        e.preventDefault();
                        setActiveTab('sheet-colors');
                    }}
                >
                    Sheet Colors
                </a>
            </h2>

            <div id="tab-content">
                {activeTab === 'main' && (
                    <div id="main" className="tab-section active">
                        <form id="calendar-scraper-form" onSubmit={(e) => e.preventDefault()}>
                            <button
                                type="button"
                                id="run-scraper-now"
                                className="button button-primary"
                                onClick={handleRunScraper}
                                disabled={isRunning}
                            >
                                {isRunning ? 'Running...' : 'Run Scraper Now'}
                            </button>

                            {isRunning && (
                                <div id="scraper-progress">
                                    <progress value="0" max="100"></progress>
                                </div>
                            )}

                            <div id="scraper-log" style={{ marginTop: '1rem' }}>
                                <pre>{log}</pre>
                            </div>
                        </form>
                    </div>
                )}

                {activeTab === 'sheet-colors' && (
                    <div id="sheet-colors" className="tab-section">
                        <h2>Tab Sheet Colors for Each Tournament Level</h2>
                        <p>Here you can configure color codes for various tournament levels.</p>
                        {/* Add React components or inputs here as needed */}
                    </div>
                )}
            </div>
        </div>
    );
};

export default App;
