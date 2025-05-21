import React, { useState, useEffect } from 'react';
import './App.scss';

const App = () => {
    const [activeTab, setActiveTab] = useState('main');
    const [isRunning, setIsRunning] = useState(false);
    const [progress, setProgress] = useState(0);
    const [log, setLog] = useState({ message: '', matches: [] });
    const [formData, setFormData] = useState({
        season: '',
        linkStructure: 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,{season},{pool},{group},{region},,,,',
        venue: '',
    });
    const [errors, setErrors] = useState({});
    const seasons = window.calendarScraperAjax?.seasons || [];

    // Validation function
    const validateForm = () => {
        const newErrors = {};

        // Validate Season
        if (!formData.season) {
            newErrors.season = 'Please select a season.';
        }

        // Validate Link Structure
        const linkStructureRegex = /^https:\/\/www\.bordtennisportalen\.dk\/DBTU\/HoldTurnering\/Stilling\/#4,\{season\},\{pool\},\{group\},\{region\},,,,$/;
        if (!formData.linkStructure) {
            newErrors.linkStructure = 'Link structure is required.';
        } else if (!linkStructureRegex.test(formData.linkStructure)) {
            newErrors.linkStructure = 'Link structure must match the expected format.';
        }

        // Validate Venue
        if (!formData.venue.trim()) {
            newErrors.venue = 'Venue name is required.';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    // Handle input changes
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: value,
        }));
        if (errors[name]) {
            setErrors((prev) => ({
                ...prev,
                [name]: '',
            }));
        }
    };

    // Simulate progress while scraping
    useEffect(() => {
        let interval;
        if (isRunning) {
            setProgress(0);
            interval = setInterval(() => {
                setProgress((prev) => {
                    if (prev >= 90) {
                        return 90;
                    }
                    return prev + 10;
                });
            }, 500);
        }
        return () => clearInterval(interval);
    }, [isRunning]);

    const handleRunScraper = async () => {
        if (!validateForm()) {
            setLog({ message: 'Please fix the errors in the form.', matches: [] });
            return;
        }

        setIsRunning(true);
        setLog({ message: 'Running scraper...', matches: [] });

        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'run_calendar_scraper',
                    _ajax_nonce: calendarScraperAjax.nonce,
                    season: formData.season,
                    link_structure: formData.linkStructure,
                    venue: formData.venue,
                }),
            });

            const data = await response.json();

            if (data.success) {
                if (data.data.error) {
                    setLog({ message: data.data.error, matches: [] });
                    setProgress(0);
                } else if (Array.isArray(data.data.message) && data.data.message.length === 0) {
                    setLog({ message: 'No matches found for venue ' + formData.venue, matches: [] });
                    setProgress(100);
                } else {
                    setLog({ message: '', matches: data.data.message });
                    setProgress(100);
                }
            } else {
                setLog({ message: data.data?.message || 'Something went wrong.', matches: [] });
                setProgress(0);
            }
        } catch (error) {
            setLog({ message: 'Error: ' + error.message, matches: [] });
            setProgress(0);
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
                    <a
                        href="#settings"
                        className={`nav-tab ${activeTab === 'settings' ? 'nav-tab-active' : ''}`}
                        onClick={(e) => {
                            e.preventDefault();
                            setActiveTab('settings');
                        }}
                    >
                        Settings
                    </a>
                </h2>

                <div id="tab-content">
                    {activeTab === 'main' && (
                        <div id="main" className="tab-section active">
                            <form id="calendar-scraper-form" onSubmit={(e) => e.preventDefault()}>
                                <div className="form-section">
                                    <label htmlFor="season-select" className="form-label">Select Season:</label>
                                    <select
                                        id="season-select"
                                        name="season"
                                        className={`form-select ${errors.season ? 'has-error' : ''}`}
                                        value={formData.season}
                                        onChange={handleInputChange}
                                    >
                                        <option value="">-- Select Season --</option>
                                        {seasons.map((season) => (
                                            <option key={season.season_value} value={season.season_value}>
                                                {season.season_name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.season && <span className="error-message">{errors.season}</span>}
                                </div>

                                <div className="form-section">
                                    <label htmlFor="link-structure" className="form-label">Link Structure:</label>
                                    <input
                                        type="text"
                                        id="link-structure"
                                        name="linkStructure"
                                        className={`form-input ${errors.linkStructure ? 'has-error' : ''}`}
                                        value={formData.linkStructure}
                                        onChange={handleInputChange}
                                        placeholder="Enter link structure name"
                                    />
                                    {errors.linkStructure && <span className="error-message">{errors.linkStructure}</span>}
                                </div>

                                <div className="form-section">
                                    <label htmlFor="venue" className="form-label">Venue:</label>
                                    <input
                                        type="text"
                                        id="venue"
                                        name="venue"
                                        className={`form-input ${errors.venue ? 'has-error' : ''}`}
                                        value={formData.venue}
                                        onChange={handleInputChange}
                                        placeholder="Enter venue name"
                                    />
                                    {errors.venue && <span className="error-message">{errors.venue}</span>}
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
                                        <progress value={progress} max="100"></progress>
                                    </div>
                                )}

                                <div id="scraper-log" className="scraper-log">
                                    {log.message ? (
                                        <pre>{log.message}</pre>
                                    ) : (
                                        <ul>
                                            {log.matches.map((match, index) => (
                                                <li key={index}>
                                                    Match {match.tid}: {match.hjemmehold} vs {match.udehold} at {match.spillested} - {match.resultat}
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            </form>
                        </div>
                    )}

                    {activeTab === 'sheet-colors' && (
                        <div id="sheet-colors" className="tab-section">
                            {/* Add content for Sheet Colors tab */}
                        </div>
                    )}

                    {activeTab === 'log-state' && (
                        <div id="log-state" className="tab-section">
                            {/* Add content for Log State tab */}
                        </div>
                    )}

                    {activeTab === 'settings' && (
                        <div id="settings" className="tab-section">
                            {/* Add content for Log State tab */}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default App;