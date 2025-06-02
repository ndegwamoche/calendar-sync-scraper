import React, { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import './Colors.scss';

const Colors = ({ season, url, regions, ageGroups }) => {
    const [googleColors, setGoogleColors] = useState([]);
    const [levelColors, setLevelColors] = useState({});
    const [selectedLevel, setSelectedLevel] = useState('');
    const [availableLevels, setAvailableLevels] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedRegion, setSelectedRegion] = useState('');
    const [selectedAgeGroup, setSelectedAgeGroup] = useState('');
    const [pools, setPools] = useState([]);
    const levels = window.calendarScraperAjax?.tournament_levels || [];

    useEffect(() => {
        const fetchPools = async () => {
            try {
                const response = await fetch(
                    `${calendarScraperAjax.ajax_url}?action=get_tournament_options&season=${season}&region=${selectedRegion}&age_group=${selectedAgeGroup}&_ajax_nonce=${calendarScraperAjax.nonce}`,
                    {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                    }
                );
                const data = await response.json();
                if (data.success) {
                    setPools(data.data.pools || []);
                } else {
                    console.error('Failed to fetch pools:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error fetching pools:', error);
            }
        };

        fetchPools();
    }, [season, selectedRegion, selectedAgeGroup]);

    useEffect(() => {
        const fetchGoogleColors = async () => {
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_google_colors',
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    setGoogleColors(Object.values(data.data)); // Store full color objects
                } else {
                    console.error('Failed to fetch Google colors:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error fetching google colors:', error);
            }
        };

        const fetchLevelColors = async () => {
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_level_colors',
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    setLevelColors(data.data); // Expects level_id -> google_color_id
                } else {
                    console.error('Failed to fetch level colors:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error fetching level colors:', error);
            }
        };

        Promise.all([fetchGoogleColors(), fetchLevelColors()])
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        setAvailableLevels(levels.filter(level => !levelColors[level.id]));
    }, [levels, levelColors]);

    const handleAssignColor = async (levelID, googleColorId) => {
        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_level_color',
                    _ajax_nonce: calendarScraperAjax.nonce,
                    level_id: levelID,
                    google_color_id: googleColorId,
                }),
            });
            const data = await response.json();
            if (data.success) {
                setLevelColors((prev) => ({ ...prev, [levelID]: googleColorId }));
                setSelectedLevel('');
            } else {
                console.error('Failed to save level color:', data.data?.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Error saving level color:', error);
        }
    };

    const handleRemoveColor = async (levelID) => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to remove the color for this level?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63638',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel',
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'remove_level_color',
                        _ajax_nonce: calendarScraperAjax.nonce,
                        level_id: levelID,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    setLevelColors((prev) => {
                        const updated = { ...prev };
                        delete updated[levelID];
                        return updated;
                    });
                } else {
                    console.error('Failed to remove level color:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error removing level color:', error);
            }
        }
    };

    const handleClearAll = async () => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to clear all color assignments?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63638',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, clear all',
            cancelButtonText: 'Cancel',
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'clear_level_colors',
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    setLevelColors({});
                    setAvailableLevels(levels);
                } else {
                    console.error('Failed to clear level colors:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error clearing level colors:', error);
            }
        }
    };

    const handleScrapingPools = async () => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to scrape pools for all regions and age groups?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63638',
            cancelButtonColor: '#666',
            confirmButtonText: 'Yes, scrape pools',
            cancelButtonText: 'Cancel',
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'run_pools_scraping',
                        season: season,
                        link_structure: url,
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {

                } else {
                    console.error('Failed to scrape pools:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error scraping pools:', error);
            }
        }
    };

    const getHexCode = (googleColorId) => {
        const colorObj = googleColors.find(color => color.google_color_id === googleColorId);
        return colorObj ? colorObj.hex_code : '#000000';
    };

    return (
        <div id="sheet-colors" className="tab-section">
            <div className="form-section dropdown-row">
                <label htmlFor="region-select" className="form-label">Regions & Groups:</label>
                <select
                    id="region-select"
                    name="region"
                    className="form-select"
                    value={selectedRegion}
                    onChange={(e) => setSelectedRegion(e.target.value)}
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
                    className="form-select"
                    value={selectedAgeGroup}
                    onChange={(e) => setSelectedAgeGroup(e.target.value)}
                >
                    <option value="">-- Select Age Group --</option>
                    {ageGroups.map((ageGroup) => (
                        <option key={ageGroup.age_group_value} value={ageGroup.age_group_value}>
                            {ageGroup.age_group_name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="form-section">
                <div className="form-control-group">
                    <label htmlFor="level-select-color" className="form-label">Tournament Level:</label>
                    <select
                        id="level-select-color"
                        className="form-select"
                        value={selectedLevel}
                        onChange={(e) => setSelectedLevel(e.target.value)}
                        disabled={availableLevels.length === 0}
                    >
                        <option value="">-- Select Tournament Level --</option>
                        {availableLevels.map((level) => (
                            <option key={level.id} value={level.id}>
                                {level.level_name}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            <div className="form-section">
                <h4>Available Colors:</h4>
                <div className="color-swatches">
                    {googleColors.map((colorObj) => (
                        <div
                            key={colorObj.google_color_id}
                            className={`color-swatch ${selectedLevel ? 'clickable' : 'disabled'}`}
                            style={{ backgroundColor: colorObj.hex_code }}
                            title={colorObj.color_name}
                            onClick={() => {
                                if (selectedLevel) {
                                    handleAssignColor(selectedLevel, colorObj.google_color_id);
                                }
                            }}
                        ></div>
                    ))}
                </div>
            </div>
            <div className="form-section">
                <h4>Assigned Colors:</h4>
                {loading ? (
                    <div className="loader">Loading...</div>
                ) : Object.keys(levelColors).length === 0 ? (
                    <p>No colors assigned.</p>
                ) : (
                    <ul className="assigned-colors">
                        {Object.entries(levelColors).map(([levelID, googleColorId]) => {
                            const level = levels.find(l => l.id === levelID);
                            const hexCode = getHexCode(googleColorId);
                            return (
                                <li key={levelID}>
                                    <span className="level-name">{level?.level_name || 'Unknown Level'}</span>
                                    <span style={{ backgroundColor: hexCode, display: 'inline-block', width: '20px', height: '20px' }} />
                                    <button
                                        className="remove-color"
                                        onClick={() => handleRemoveColor(levelID)}
                                        title="Remove color"
                                    >
                                        ×
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>

            <div className="form-section button-group" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <button
                    type="button"
                    className="button button-primary"
                    onClick={handleScrapingPools}
                >
                    Run Pools Scraping
                </button>
                <button
                    type="button"
                    className="button button-secondary"
                    onClick={handleClearAll}
                >
                    Clear All Colors
                </button>
            </div>

        </div>
    );
};

export default Colors;