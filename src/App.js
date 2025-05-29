import React, { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import Logs from './Logs';
import Colors from './Colors';
import Settings from './Settings';
import './App.scss';

const App = () => {
    const [activeTab, setActiveTab] = useState('main');
    const [isRunning, setIsRunning] = useState(false);
    const [isClearing, setIsClearing] = useState(false); // Added for loader
    const [progress, setProgress] = useState(0);
    const [log, setLog] = useState({ message: '', matches: [] });
    const [formData, setFormData] = useState({
        season: '42024',
        linkStructure: 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,{season},{pool},{group},{region},,,,',
        venue: '',
        region: '4004',
        ageGroup: '4006',
        pool: '14822',
    });
    const [errors, setErrors] = useState({});
    const [showDropdowns, setShowDropdowns] = useState(false);
    const [pools, setPools] = useState([]);
    const seasons = window.calendarScraperAjax?.seasons || [];
    const regions = window.calendarScraperAjax?.regions || [];
    const ageGroups = window.calendarScraperAjax?.age_groups || [];
    const levels = window.calendarScraperAjax?.tournament_levels || [];
    const [logInfo, setLogInfo] = useState([]);

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
                ...prevErrors,
                [name]: '',
            }));
        }
    };

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

    // Fetch logInfo when the "Log State" tab is activated
    useEffect(() => {
        if (activeTab === 'log-state') {
            const fetchLogInfo = async () => {
                try {
                    const response = await fetch(`${calendarScraperAjax.ajax_url}?action=get_log_info&_ajax_nonce=${calendarScraperAjax.nonce}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                    });
                    const data = await response.json();
                    if (data.success) {
                        const formattedLogs = data.data.map(log => {
                            const duration = log.close_datetime
                                ? Math.floor((new Date(log.close_datetime) - new Date(log.start_datetime)) / 1000)
                                : null;

                            return {
                                id: log.id,
                                message: `Scraper run for season ${log.season_id}, status: ${log.status}, matches: ${log.total_matches}`,
                                start_datetime: log.start_datetime,
                                close_datetime: log.close_datetime,
                                duration,
                                status: log.status,
                                details: log.error_message,
                                total_matches: parseInt(log.total_matches, 10),
                            };
                        });
                        setLogInfo(formattedLogs);
                    } else {
                        console.error('Failed to fetch log info:', data.data?.message || 'Unknown error');
                        setLogInfo([]); // Clear logInfo on error
                    }
                } catch (error) {
                    console.error('Error fetching log info:', error);
                    setLogInfo([]); // Clear logInfo on error
                }
            };

            fetchLogInfo();
        }
    }, [activeTab]);

    const handleRunScraper = async () => {
        if (!validateForm()) {
            setLog({ message: 'Please fix the errors in the form.', matches: [] });
            return;
        }

        if (pools.length === 0) {
            setLog({ message: 'No pools available to scrape.', matches: [] });
            return;
        }

        setIsRunning(true);
        setLog({ message: 'Starting scraper...', matches: [] });
        setProgress(0);

        let allMatches = [];
        let completedRequests = 0;
        const sessionId = Date.now().toString();

        try {
            for (const pool of pools) {
                setLog(prevLog => ({
                    ...prevLog,
                    message: `Scraping pool ${pool.tournament_level} - ${pool.pool_name} (${pool.pool_value})...`,
                    matches: allMatches,
                }));

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
                        age_group_name: pool.age_group_name,
                        region_name: pool.region_name,
                        season_name: pool.season_name,
                        pool: pool.pool_value,
                        pool_name: pool.pool_name,
                        tournament_level: pool.tournament_level,
                        color_id: pool.google_color_id,
                        session_id: sessionId,
                        total_pools: pools.length
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    if (data.data.error) {
                        setLog(prevLog => ({
                            ...prevLog,
                            message: `Error in pool ${pool.pool_value}: ${data.data.error}`,
                            matches: allMatches,
                        }));
                    } else if (Array.isArray(data.data.message) && data.data.message.length === 0) {
                        setLog(prevLog => ({
                            ...prevLog,
                            message: `No matches found for pool ${pool.pool_value} at venue ${formData.venue}`,
                            matches: allMatches,
                        }));
                    } else {
                        allMatches = [...allMatches, ...data.data.message];
                        setLog({
                            message: `Completed scraping pool ${pool.tournament_level} - ${pool.pool_name} (${pool.pool_value})`,
                            matches: allMatches,
                        });
                    }
                } else {
                    setLog(prevLog => ({
                        ...prevLog,
                        message: `Error in pool ${pool.pool_value}: ${data.data?.message || 'Something went wrong.'}`,
                        matches: allMatches,
                    }));
                }

                completedRequests++;
                setProgress((completedRequests / pools.length) * 100);
            }

            if (allMatches.length === 0) {
                setLog({
                    message: `No matches found for any pools at venue ${formData.venue}`,
                    matches: [],
                });
            } else {
                setLog({
                    message: `All pools scraped successfully! Found ${allMatches.length} matches`,
                    matches: allMatches,
                });
            }
        } catch (error) {
            setLog({
                message: `Error: ${error.message}`,
                matches: allMatches,
            });
            setProgress(0);
        } finally {
            setIsRunning(false);
        }
    };

    const handleClearMatches = async () => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to clear all matches?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63638',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, clear all',
            cancelButtonText: 'Cancel',
        });

        if (result.isConfirmed) {
            setIsClearing(true); // Start loader
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'clear_google_calendar_events',
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    setLog({ message: 'All matches cleared successfully.', matches: [] });
                } else {
                    setLog({ message: `Failed to clear matches: ${data.data?.message || 'Unknown error'}`, matches: [] });
                }
            } catch (error) {
                setLog({ message: `Error clearing matches: ${error.message}`, matches: [] });
            } finally {
                setIsClearing(false); // Stop loader
            }
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
                                        <label htmlFor="season-select" className="form-label">Season:</label>
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
                                                    {pool.tournament_level} - ${pool.pool_name}
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

                                <div className="form-section button-group" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                    <button
                                        type="button"
                                        id="run-scraper-now"
                                        className="button button-primary"
                                        onClick={handleRunScraper}
                                        disabled={isRunning || isClearing}
                                    >
                                        {isRunning ? 'Running...' : 'Run Scraper Now'}
                                    </button>
                                    <button
                                        type="button"
                                        id="clear-matches"
                                        className="button button-secondary"
                                        onClick={handleClearMatches}
                                        disabled={isRunning || isClearing}
                                    >
                                        Clear All Matches
                                    </button>
                                </div>

                                {(isRunning || isClearing) && (
                                    <div id="scraper-progress">
                                        {isRunning && <progress value={progress} max="100"></progress>}
                                        {isClearing && <div className="loader">Clearing...</div>}
                                    </div>
                                )}

                                <div id="scraper-log" className="scraper-log">
                                    {log.message && <pre>{log.message}</pre>}
                                    {log.matches.length > 0 ? (
                                        <ul>
                                            {log.matches.map((match, index) => (
                                                <li key={index}>
                                                    Match ${match.tid}: ${match.hjemmehold} vs ${match.udehold} at ${match.spillested} - ${match.resultat}
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <p>No matches to display yet.</p>
                                    )}
                                </div>
                            </form>
                        </div>
                    )}

                    {activeTab === 'sheet-colors' && (
                        <Colors levels={levels} />
                    )}

                    {activeTab === 'log-state' && (
                        <Logs logInfo={logInfo} />
                    )}

                    {activeTab === 'settings' && (
                        <Settings />
                    )}
                </div>
            </div>
        </div>
    );
};

export default App;