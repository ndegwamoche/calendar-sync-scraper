import React, { useState, useEffect } from 'react';
import './App.scss';

const App = () => {
    const [activeTab, setActiveTab] = useState('main');
    const [isRunning, setIsRunning] = useState(false);
    const [progress, setProgress] = useState(0);
    const [log, setLog] = useState({ message: '', matches: [] });
    const [formData, setFormData] = useState({
        season: '42024',
        linkStructure: 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,{season},{pool},{group},{region},,,,',
        venue: '',
        region: '4004', // Default to ØST (Sjælland, Lolland F.)
        ageGroup: '4006', // Default to Senior
        pool: '14822', // Default to Pool
    });
    const [errors, setErrors] = useState({});
    const [showDropdowns, setShowDropdowns] = useState(false);
    const [pools, setPools] = useState([]);
    const seasons = window.calendarScraperAjax?.seasons || [];
    const regions = window.calendarScraperAjax?.regions || [];
    const ageGroups = window.calendarScraperAjax?.age_groups || [];

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

    // Fetch dropdown options from DB when season, region, or age group changes
    useEffect(() => {
        if (formData.season) {
            fetch(`${calendarScraperAjax.ajax_url}?action=get_tournament_options&season=${formData.season}&region=${formData.region}&age_group=${formData.ageGroup}&_ajax_nonce=${calendarScraperAjax.nonce}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        setPools(data.data.pools || []);
                    }
                })
                .catch((error) => console.error('Error fetching pools:', error));
        } else {
            setPools([]);
        }
    }, [formData.season, formData.region, formData.ageGroup]);

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
                    region: formData.region,
                    age_group: formData.ageGroup,
                    pool: formData.pool,
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
                                    <div className="form-control-group">
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
                                    </div>
                                    {errors.season && <span className="error-message">{errors.season}</span>}
                                    <a href="#" className="advanced-link" onClick={(e) => { e.preventDefault(); setShowDropdowns(!showDropdowns); }}>Advanced Settings</a>
                                </div>

                                {showDropdowns && (
                                    <div className="form-section dropdown-row">
                                        <label htmlFor="region-select" className="form-label"></label>
                                        <select
                                            id="region-select"
                                            name="region"
                                            className={`form-select ${errors.region ? 'has-error' : ''}`}
                                            value={formData.region}
                                            onChange={handleInputChange}
                                        >
                                            <option value="">-- Select Region --</option>
                                            {regions.map((region) => (
                                                <option key={region.region_value} value={region.region_value}>
                                                    {region.region_name}
                                                </option>
                                            ))}
                                        </select>

                                        <select
                                            id="age-group-select"
                                            name="ageGroup"
                                            className={`form-select ${errors.ageGroup ? 'has-error' : ''}`}
                                            value={formData.ageGroup}
                                            onChange={handleInputChange}
                                        >
                                            <option value="">-- Select Age Group --</option>
                                            {ageGroups.map((ageGroup) => (
                                                <option key={ageGroup.age_group_value} value={ageGroup.age_group_value}>
                                                    {ageGroup.age_group_name}
                                                </option>
                                            ))}
                                        </select>

                                        <select
                                            id="pool-select"
                                            name="pool"
                                            className={`form-select ${errors.pool ? 'has-error' : ''}`}
                                            value={formData.pool}
                                            onChange={handleInputChange}
                                        >
                                            <option value="">-- Select Pool --</option>
                                            {pools.map((pool) => (
                                                <option key={pool.pool_value} value={pool.pool_value}>
                                                    {pool.tournament_level} - {pool.pool_name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                )}

                                <div className="form-section">
                                    <div className="form-control-group">
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
                                    </div>
                                    {errors.linkStructure && <span className="error-message">{errors.linkStructure}</span>}
                                </div>

                                <div className="form-section">
                                    <div className="form-control-group">
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
                                    </div>
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