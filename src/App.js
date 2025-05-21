import React, { useState } from 'react';
import './App.scss';

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
        <div className="wrap">
            <h1>Calendar Sync Scraper</h1>
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

                    <a
                        href="#log-state"
                        className={`nav-tab ${activeTab === 'log-state' ? 'nav-tab-active' : ''}`}
                        onClick={(e) => {
                            e.preventDefault();
                            setActiveTab('log-state');
                        }}
                    >
                        Log State
                    </a>
                </h2>

                <div id="tab-content">
                    {activeTab === 'main' && (
                        <div id="main" className="tab-section active">
                            <form id="calendar-scraper-form" onSubmit={(e) => e.preventDefault()}>
                                <div className="form-section">
                                    <label htmlFor="season-select" className="form-label">Select Season:</label>
                                    <select id="season-select" name="season" className="form-select">
                                        <option value="">-- Select Season --</option>
                                        <option value="2025">2025</option>
                                        <option value="2024">2024</option>
                                        <option value="2023">2023</option>
                                        <option value="2022-2024">2022–2024</option>
                                        <option value="2020-2024">2020–2024</option>
                                    </select>
                                </div>

                                <div className="form-section">
                                    <label htmlFor="venue" className="form-label">Link Structure:</label>
                                    <input
                                        type="text"
                                        id="link-structure"
                                        name="link-structure"
                                        className="form-input"
                                        placeholder="Enter link structure name"
                                    />
                                </div>

                                <div className="form-section">
                                    <label htmlFor="venue" className="form-label">Venue:</label>
                                    <input
                                        type="text"
                                        id="venue"
                                        name="venue"
                                        className="form-input"
                                        placeholder="Enter venue name"
                                    />
                                </div>

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

                        </div>
                    )}

                    {activeTab === 'log-state' && (
                        <div id="log-state" className="tab-section">

                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default App;
