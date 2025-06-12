import React, { useState, useEffect, useCallback } from 'react';
import Swal from 'sweetalert2';
import Logs from './Logs';
import Colors from './Colors';
import Settings from './Settings';
import './App.scss';

const App = () => {
    const [activeTab, setActiveTab] = useState('main');
    const [isRunning, setIsRunning] = useState(false);
    const [isClearing, setIsClearing] = useState(false);
    const [progress, setProgress] = useState(0);
    const [log, setLog] = useState({ message: '', matches: [] });
    const [formData, setFormData] = useState({
        season: '42024',
        linkStructure: 'https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#4,{season},{pool},{group},{region},,,,',
        venue: '',
    });
    const [errors, setErrors] = useState({});
    const seasons = window.calendarScraperAjax?.seasons || [];
    const [logInfo, setLogInfo] = useState([]);
    const regions = window.calendarScraperAjax?.regions || [];
    const ageGroups = window.calendarScraperAjax?.age_groups || [];
    const sessionId = Date.now().toString();

    const validateForm = () => {
        const newErrors = {};
        if (!formData.season) newErrors.season = 'Please select a season.';
        const linkStructureRegex = /^https:\/\/www\.bordtennisportalen\.dk\/DBTU\/HoldTurnering\/Stilling\/#4,\{season\},\{pool\},\{group\},\{region\},,,,$/;
        if (!formData.linkStructure) newErrors.linkStructure = 'Link structure is required.';
        else if (!linkStructureRegex.test(formData.linkStructure)) newErrors.linkStructure = 'Link structure must match the expected format.';
        if (!formData.venue.trim()) newErrors.venue = 'Venue name is required.';
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
        if (errors[name]) setErrors((prev) => ({ ...prev, [name]: '' }));
    };

    useEffect(() => {
        if (activeTab === 'log-state') {
            const fetchLogInfo = async () => {
                try {
                    const response = await fetch(`${calendarScraperAjax.ajax_url}?action=get_log_info&_ajax_nonce=${calendarScraperAjax.nonce}`, {
                        method: 'GET',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
                        setLogInfo([]);
                    }
                } catch (error) {
                    console.error('Error fetching log info:', error);
                    setLogInfo([]);
                }
            };
            fetchLogInfo();
        }
    }, [activeTab]);

    const debounce = (func, wait) => {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    };

    async function get_scraper_progress(sessionId) {
        try {
            const response = await fetch(`${calendarScraperAjax.ajax_url}?action=get_scraper_progress&session_id=${encodeURIComponent(sessionId)}&total_matches=175`);
            const data = await response.json();

            if (data.success) {
                return data.data;
            } else {
                return null;
            }
        } catch (error) {
            return null;
        }
    }

    const handleRunAllScrapers = useCallback(async () => {
        if (!validateForm()) {
            setLog({ message: 'Please fix the errors in the form.', matches: [] });
            return;
        }

        const result = await Swal.fire({
            title: 'Large Scraping Task',
            text: `You are about to scrape all region and age group combinations. This may take a while. Continue?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
        });

        let allMatches = [];

        if (!result.isConfirmed) {
            setLog({ message: 'Scraping cancelled by user.', matches: [] });
            return;
        } else {

            const sessionId = Date.now().toString();

            setIsRunning(true);
            setLog({ message: 'Starting scraper for all regions and age groups...', matches: [] });
            setProgress(0);

            const intervalId = setInterval(async () => {
                const progressData = await get_scraper_progress(sessionId);
                if (!progressData || progressData.status === 'completed') {
                    clearInterval(intervalId);
                    setProgress(100);
                    return;
                }
                setProgress(progressData.progress);
                setLog({
                    message: progressData.message,
                    matches: allMatches,
                });
            }, 1000);

            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'run_all_calendar_scraper',
                        _ajax_nonce: calendarScraperAjax.nonce,
                        season: formData.season,
                        link_structure: formData.linkStructure,
                        venue: formData.venue,
                        session_id: sessionId,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    const matches = data.data.matches;

                    if (matches.length === 0) {
                        setLog({
                            message: `No matches found`,
                            matches: allMatches,
                        });
                        setProgress(100);
                        setIsRunning(false);
                    } else {
                        allMatches = [...allMatches, ...matches];
                        setLog({
                            message: `✅ Scraping completed! Found ${matches.length} matches`,
                            matches: allMatches,
                        });
                        setProgress(100);
                        setIsRunning(false);
                    }
                } else {
                    setLog({
                        message: `❌ Error ${data.data?.message || 'Something went wrong.'}`,
                        matches: allMatches,
                    });
                    setProgress(0);
                    setIsRunning(false);
                }

            } catch (error) {
                setLog({ message: `Error: ${error.message}`, matches: [] });
                setProgress(0);
                setIsRunning(false);
            }
        }

    }, [formData]);

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
            setIsClearing(true);
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete_all_events_permanently',
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
                setIsClearing(false);
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
                        onClick={(e) => { e.preventDefault(); setActiveTab('main'); }}
                    >
                        Main
                    </a>
                    <a
                        href="#sheet-colors"
                        className={`nav-tab ${activeTab === 'sheet-colors' ? 'nav-tab-active' : ''}`}
                        onClick={(e) => { e.preventDefault(); setActiveTab('sheet-colors'); }}
                    >
                        Sheet Colors
                    </a>
                    <a
                        href="#log-state"
                        className={`nav-tab ${activeTab === 'log-state' ? 'nav-tab-active' : ''}`}
                        onClick={(e) => { e.preventDefault(); setActiveTab('log-state'); }}
                    >
                        Log State
                    </a>
                    <a
                        href="#settings"
                        className={`nav-tab ${activeTab === 'settings' ? 'nav-tab-active' : ''}`}
                        onClick={(e) => { e.preventDefault(); setActiveTab('settings'); }}
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
                                        onClick={handleRunAllScrapers}
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
                                        {isRunning && (
                                            <>
                                                <progress value={progress} max="100"></progress>
                                                <span>{progress}%</span>
                                            </>
                                        )}
                                        {isClearing && <div className="loader">Clearing...</div>}
                                    </div>
                                )}

                                <div id="scraper-log" className="scraper-log">
                                    {log.message && (
                                        <pre
                                            dangerouslySetInnerHTML={{
                                                __html: log.message.replace(/\\n/g, '\n'),
                                            }}
                                        />
                                    )}
                                    {log.matches.length > 0 ? (
                                        <ul>
                                            {log.matches.map((match, index) => (
                                                <li key={index}>
                                                    <strong>Match {match.no}</strong> – <em>{match.tid}</em>: <strong>{match.hjemmehold}</strong> ({match.hjemmehold_id})
                                                    vs <strong>{match.udehold}</strong> ({match.udehold_id}) at <strong>{match.spillested}</strong> —
                                                    Result: <strong>{match.resultat}</strong>
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
                        <Colors season={formData.season} url={formData.linkStructure} regions={regions} ageGroups={ageGroups} />
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