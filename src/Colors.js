import React, { useState } from 'react';
import './Colors.scss';

const Colors = ({ levels }) => {
    // Predefined colors (10 colors)
    const availableColors = [
        '#FF0000', // Red
        '#00FF00', // Green
        '#0000FF', // Blue
        '#FFFF00', // Yellow
        '#FF00FF', // Magenta
        '#00FFFF', // Cyan
        '#FFA500', // Orange
        '#800080', // Purple
        '#008000', // Dark Green
        '#FFC0CB', // Pink
    ];

    const [poolColors, setPoolColors] = useState({});
    const [poolFilter, setPoolFilter] = useState('');
    const [selectedPool, setSelectedPool] = useState('');

    // Filter pools based on search input
    const filteredPools = levels.filter(
        (pool) =>
            !poolColors[pool.pool_value] &&
            (poolFilter === '' ||
                pool.level_name.toLowerCase().includes(poolFilter.toLowerCase()) ||
                pool.tournament_level.toLowerCase().includes(poolFilter.toLowerCase()))
    );

    // Handle color assignment
    const handleAssignColor = async (poolId, color) => {
        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'save_pool_color',
                    _ajax_nonce: calendarScraperAjax.nonce,
                    pool_id: poolId,
                    color,
                }),
            });
            const data = await response.json();
            if (data.success) {
                setPoolColors((prev) => ({ ...prev, [poolId]: color }));
                setSelectedPool(''); // Reset dropdown
            } else {
                console.error('Failed to save pool color:', data.data?.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Error saving pool color:', error);
        }
    };

    // Handle clear colors with confirmation
    const handleClearColors = async () => {
        // if (window.confirm('Are you sure you want to clear all pool color assignments?')) {
        //     try {
        //         const response = await fetch(calendarScraperAjax.ajax_url, {
        //             method: 'POST',
        //             headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        //             body: new URLSearchParams({
        //                 action: 'clear_pool_colors',
        //                 _ajax_nonce: calendarScraperAjax.nonce,
        //             }),
        //         });
        //         const data = await response.json();
        //         if (data.success) {
        //             setPoolColors({});
        //             setSelectedPool('');
        //         } else {
        //             console.error('Failed to clear pool colors:', data.data?.message || 'Unknown error');
        //         }
        //     } catch (error) {
        //         console.error('Error clearing pool colors:', error);
        //     }
        // }
    };

    return (
        <div id="sheet-colors" className="tab-section">
            <div className="form-section">
                <label htmlFor="pool-select-color" className="form-label">Select Tournament Level:</label>
                <select
                    id="pool-select-color"
                    className="form-select"
                    value={selectedPool}
                    onChange={(e) => setSelectedPool(e.target.value)}
                >
                    <option value="">-- Select Pool --</option>
                    {filteredPools.map((pool) => (
                        <option key={pool.id} value={pool.id}>
                            {pool.level_name}
                        </option>
                    ))}
                </select>
            </div>
            <div className="form-section">
                <h4>Available Colors:</h4>
                <div className="color-swatches">
                    {availableColors.map((color) => (
                        <div
                            key={color}
                            className={`color-swatch ${selectedPool ? 'clickable' : 'disabled'}`}
                            style={{ backgroundColor: color }}
                            title={color}
                            onClick={() => {
                                if (selectedPool) {
                                    handleAssignColor(selectedPool, color);
                                }
                            }}
                        ></div>
                    ))}
                </div>
            </div>
            <div className="form-section">
                <h4>Assigned Colors:</h4>
                {Object.keys(poolColors).length === 0 ? (
                    <p>No colors assigned.</p>
                ) : (
                    <ul>
                        {Object.entries(poolColors).map(([poolId, color]) => {
                            const pool = pools.find((p) => p.pool_value === poolId);
                            return (
                                <li key={poolId}>
                                    {pool ? `${pool.tournament_level} - ${pool.pool_name}` : poolId}:{' '}
                                    <span
                                        className="color-swatch"
                                        style={{ backgroundColor: color }}
                                    ></span>{' '}
                                    {color}
                                </li>
                            );
                        })}
                    </ul>
                )}
            </div>
            <button
                type="button"
                className="button button-secondary"
                onClick={handleClearColors}
            >
                Clear All Colors
            </button>
        </div>
    );
};

export default Colors;