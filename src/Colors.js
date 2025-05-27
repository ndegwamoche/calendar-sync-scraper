import React, { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import './Colors.scss';

const Colors = ({ levels }) => {
    const availableColors = [
        '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF',
        '#00FFFF', '#FFA500', '#800080', '#008000', '#FFC0CB',
    ];

    const [googleColors, setGoogleColors] = useState([]);
    const [levelColors, setLevelColors] = useState({});
    const [selectedLevel, setSelectedLevel] = useState('');
    const [availableLevels, setAvailableLevels] = useState([]);
    const [loading, setLoading] = useState(true);

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
                    // Extract hex codes from the object values
                    const colors = Object.values(data.data).map(color => color.hex_code);
                    setGoogleColors(colors);
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
                    setLevelColors(data.data);
                } else {
                    console.error('Failed to fetch level colors:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error fetching level colors:', error);
            }
        };

        // Run both fetches concurrently and set loading state
        Promise.all([fetchGoogleColors(), fetchLevelColors()])
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        setAvailableLevels(levels.filter(level => !levelColors[level.id]));
    }, [levels, levelColors]);

    const handleAssignColor = async (levelID, color) => {
        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_level_color',
                    _ajax_nonce: calendarScraperAjax.nonce,
                    level_id: levelID,
                    color,
                }),
            });
            const data = await response.json();
            if (data.success) {
                setLevelColors((prev) => ({ ...prev, [levelID]: color }));
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

    return (
        <div id="sheet-colors" className="tab-section">
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
                    {googleColors.map((color) => (
                        <div
                            key={color}
                            className={`color-swatch ${selectedLevel ? 'clickable' : 'disabled'}`}
                            style={{ backgroundColor: color }}
                            title={color}
                            onClick={() => {
                                if (selectedLevel) {
                                    handleAssignColor(selectedLevel, color);
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
                        {Object.entries(levelColors).map(([levelID, color]) => {
                            const level = levels.find(l => l.id === levelID);
                            return (
                                <li key={levelID}>
                                    <span className="level-name">{level?.level_name || 'Unknown Level'}</span>
                                    <span style={{ backgroundColor: color, display: 'inline-block', width: '20px', height: '20px' }} />
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
            <button
                type="button"
                className="button button-secondary"
                onClick={handleClearAll}
            >
                Clear All Colors
            </button>
        </div>
    );
};

export default Colors;