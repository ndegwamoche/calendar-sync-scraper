import React, { useState, useEffect } from 'react';
import Swal from 'sweetalert2';
import './Colors.scss';

const Colors = ({ season, url, regions, ageGroups }) => {
    const [googleColors, setGoogleColors] = useState([]);
    const [levelColors, setLevelColors] = useState({});
    const [selectedLevel, setSelectedLevel] = useState('');
    const [availableLevels, setAvailableLevels] = useState([]);
    const [allLevels, setAllLevels] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedRegion, setSelectedRegion] = useState('');
    const [selectedAgeGroup, setSelectedAgeGroup] = useState('');
    const [pools, setPools] = useState([]);
    const [levelsLoading, setLevelsLoading] = useState(false);
    const [log, setLog] = useState({ message: '' });
    const [isScraping, setIsScraping] = useState(false);

    useEffect(() => {
        const fetchAllLevels = async () => {
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_all_tournament_levels',
                        season_id: season,
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    const levels = Array.isArray(data.data) ? data.data : [];
                    setAllLevels(levels);
                    console.log('All levels:', levels);
                } else {
                    console.error('Failed to fetch all levels:', data.data?.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error fetching all levels:', error);
            }
        };
        fetchAllLevels();
    }, []);

    useEffect(() => {
        const fetchPools = async () => {
            try {
                const response = await fetch(
                    `${calendarScraperAjax.ajax_url}?action=get_tournament_options&season=${season}®ion=${selectedRegion}&age_group=${selectedAgeGroup}&_ajax_nonce=${calendarScraperAjax.nonce}`,
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
                    setGoogleColors(Object.values(data.data));
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

        Promise.all([fetchGoogleColors(), fetchLevelColors()])
            .finally(() => setLoading(false));
    }, []);


    useEffect(() => {
        const fetchLevels = async () => {
            if (!selectedRegion || !selectedAgeGroup) {
                setAvailableLevels([]);
                return;
            }

            setLevelsLoading(true);
            try {
                const response = await fetch(calendarScraperAjax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'get_tournament_levels_by_region_age',
                        season_id: season,
                        region_id: selectedRegion,
                        age_group_id: selectedAgeGroup,
                        _ajax_nonce: calendarScraperAjax.nonce,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    const levels = Array.isArray(data.data.data) ? data.data.data : [];
                    setAvailableLevels(levels);
                    console.log('Fetched levels:', levels);
                } else {
                    console.error('Failed to fetch levels:', data.data?.message || 'Unknown error');
                    setAvailableLevels([]);
                }
            } catch (error) {
                console.error('Error fetching levels:', error);
                setAvailableLevels([]);
            } finally {
                setLevelsLoading(false);
            }
        };

        fetchLevels();
    }, [selectedRegion, selectedAgeGroup, season]);

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
                setSelectedLevel(''); // Reset dropdown to default
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
                    // No need to update availableLevels here since the filter will handle it
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
            setIsScraping(true);
            setLog({ message: 'Starting pool scraping for all regions and age groups...' });

            let allMessages = [];
            let hasErrors = false;
            let insertedLevels = 0;
            let insertedPools = 0;
            let skippedLevels = 0;
            let skippedPools = 0;

            for (const region of regions) {
                for (const ageGroup of ageGroups) {
                    setLog(prevLog => ({
                        message: `Scraping region: ${region.region_name} (${region.region_value}), age group: ${ageGroup.age_group_name} (${ageGroup.age_group_value})...\n${prevLog.message}`,
                    }));

                    try {
                        const fetchResponse = await fetch(calendarScraperAjax.ajax_url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                action: 'fetch_page_html',
                                season: season,
                                link_structure: url,
                                region: region.region_value,
                                age_group: ageGroup.age_group_value,
                                _ajax_nonce: calendarScraperAjax.nonce,
                            }),
                        });

                        if (!fetchResponse.ok) {
                            throw new Error(`HTTP error! Status: ${fetchResponse.status}`);
                        }

                        const fetchData = await fetchResponse.json();
                        if (!fetchData.success) {
                            throw new Error(fetchData.error);
                        }

                        allMessages = [...allMessages, ...fetchData.messages];

                        const parser = new DOMParser();
                        const doc = parser.parseFromString(fetchData.html, 'text/html');
                        const tableRows = doc.querySelectorAll('table.selectgroup tr');

                        if (tableRows.length === 0) {
                            allMessages.push(`No selectgroup table rows found for region ${region.region_name} (${region.region_value}), age group ${ageGroup.age_group_name} (${ageGroup.age_group_value})`);
                            hasErrors = true;
                            continue;
                        }

                        const tournamentData = {};
                        let currentLevel = null;

                        tableRows.forEach(row => {
                            const rowClass = row.className || '';

                            if (rowClass.includes('divisionrow')) {
                                const h3 = row.querySelector('td h3');
                                const levelName = h3 ? h3.textContent.trim() : 'Unknown Level';
                                currentLevel = levelName;
                                tournamentData[currentLevel] = [];
                                allMessages.push(`Found level: ${levelName}`);
                            } else if (rowClass.includes('grouprow') && currentLevel !== null) {
                                const link = row.querySelector('td a');
                                if (link) {
                                    const poolName = link.textContent.trim();
                                    const onclick = link.getAttribute('onclick') || '';
                                    const match = onclick.match(/ShowStanding\('[^']*',\s*'[^']*',\s*'(\d+)'/);
                                    const poolValue = match ? match[1] : '';

                                    if (poolValue) {
                                        tournamentData[currentLevel].push({
                                            pool_value: poolValue,
                                            pool_name: poolName
                                        });
                                        allMessages.push(`Found pool: ${poolName} (${poolValue}) under level: ${currentLevel}`);
                                    }
                                }
                            }
                        });

                        for (const [levelName, pools] of Object.entries(tournamentData)) {
                            if (pools.length === 0) {
                                allMessages.push(`No pools found for level: ${levelName}, skipping...`);
                                continue;
                            }

                            const isPlayoff = levelName.toLowerCase().includes('slutspil') || levelName.toLowerCase().includes('kval.kampe') ? 1 : 0;

                            const checkLevelResponse = await fetch(calendarScraperAjax.ajax_url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: new URLSearchParams({
                                    action: 'check_tournament_level',
                                    level_name: levelName,
                                    season_id: season,
                                    region_id: region.region_value,
                                    age_group_id: ageGroup.age_group_value,
                                    _ajax_nonce: calendarScraperAjax.nonce,
                                }),
                            });

                            const checkLevelData = await checkLevelResponse.json();
                            if (!checkLevelData.success) {
                                allMessages.push(`Error checking level ${levelName}: ${checkLevelData.error}`);
                                hasErrors = true;
                                continue;
                            }

                            if (!checkLevelData.exists) {
                                const insertLevelResponse = await fetch(calendarScraperAjax.ajax_url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: new URLSearchParams({
                                        action: 'insert_tournament_level',
                                        level_name: levelName,
                                        season_id: season,
                                        region_id: region.region_value,
                                        age_group_id: ageGroup.age_group_value,
                                        _ajax_nonce: calendarScraperAjax.nonce,
                                    }),
                                });

                                const insertLevelData = await insertLevelResponse.json();
                                if (insertLevelData.success) {
                                    insertedLevels++;
                                    allMessages.push(`Successfully inserted level: ${levelName} (ID: ${insertLevelData.level_id})`);
                                } else {
                                    allMessages.push(`Failed to insert level: ${levelName} - ${insertLevelData.error}`);
                                    hasErrors = true;
                                    continue;
                                }
                            } else {
                                skippedLevels++;
                                allMessages.push(`Skipped existing level: ${levelName} (ID: ${checkLevelData.level_id})`);
                            }

                            for (const pool of pools) {
                                const checkPoolResponse = await fetch(calendarScraperAjax.ajax_url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: new URLSearchParams({
                                        action: 'check_tournament_pool',
                                        tournament_level: levelName,
                                        pool_value: pool.pool_value,
                                        season_id: season,
                                        region_id: region.region_value,
                                        age_group_id: ageGroup.age_group_value,
                                        _ajax_nonce: calendarScraperAjax.nonce,
                                    }),
                                });

                                const checkPoolData = await checkPoolResponse.json();
                                if (!checkPoolData.success) {
                                    allMessages.push(`Error checking pool ${pool.pool_name} (${pool.pool_value}): ${checkPoolData.error}`);
                                    hasErrors = true;
                                    continue;
                                }

                                if (checkPoolData.exists) {
                                    skippedPools++;
                                    allMessages.push(`Skipped duplicate pool: ${levelName} - ${pool.pool_name} (${pool.pool_value})`);
                                    continue;
                                }

                                const insertPoolResponse = await fetch(calendarScraperAjax.ajax_url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: new URLSearchParams({
                                        action: 'insert_tournament_pool',
                                        tournament_level: levelName,
                                        pool_name: pool.pool_name,
                                        pool_value: pool.pool_value,
                                        is_playoff: isPlayoff,
                                        season_id: season,
                                        region_id: region.region_value,
                                        age_group_id: ageGroup.age_group_value,
                                        _ajax_nonce: calendarScraperAjax.nonce,
                                    }),
                                });

                                const insertPoolData = await insertPoolResponse.json();
                                if (insertPoolData.success) {
                                    insertedPools++;
                                    allMessages.push(`Successfully inserted pool: ${levelName} - ${pool.pool_name} (${pool.pool_value}) (ID: ${insertPoolData.pool_id})`);
                                } else {
                                    allMessages.push(`Failed to insert pool: ${levelName} - ${pool.pool_name} (${pool.pool_value}) - ${insertPoolData.error}`);
                                    hasErrors = true;
                                }
                            }
                        }
                    } catch (error) {
                        hasErrors = true;
                        allMessages.push(`Error scraping region ${region.region_name} (${region.region_value}), age group ${ageGroup.age_group_name} (${ageGroup.age_group_value}): ${error.message}`);
                    }
                }
            }

            const summaryMessage = `Scrape completed: Inserted ${insertedLevels} levels and ${insertedPools} pools, skipped ${skippedLevels} levels and ${skippedPools} pools`;
            allMessages.push(summaryMessage);
            const finalMessage = allMessages.join('\n');
            setLog({ message: hasErrors ? `Scraping completed with errors:\n${finalMessage}` : `Scraping completed successfully:\n${finalMessage}` });
            setIsScraping(false);
        }
    };

    const getHexCode = (googleColorId) => {
        const colorObj = googleColors.find(color => color.google_color_id === googleColorId);
        return colorObj ? colorObj.hex_code : '#000000';
    };

    // Filter availableLevels to exclude levels that already have a color assigned
    const unassignedLevels = Array.isArray(availableLevels)
        ? availableLevels.filter(level => !Object.keys(levelColors).includes(level.id))
        : [];

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
                        disabled={levelsLoading || !Array.isArray(availableLevels) || unassignedLevels.length === 0}
                    >
                        <option value="">-- Select Tournament Level --</option>
                        {levelsLoading ? (
                            <option value="" disabled>Loading levels...</option>
                        ) : unassignedLevels.length > 0 ? (
                            unassignedLevels.map((level) => (
                                <option key={level.id} value={level.id}>
                                    {level.level_name}
                                </option>
                            ))
                        ) : (
                            <option value="" disabled>No unassigned levels available</option>
                        )}
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
                            const level = allLevels.find(l => l.id === levelID);
                            const hexCode = getHexCode(googleColorId);
                            return (
                                <li key={levelID}>
                                    <span className="level-name">{level?.level_name || 'Unknown Level'}</span>
                                    <span
                                        style={{
                                            backgroundColor: hexCode,
                                            display: 'inline-block',
                                            width: '20px',
                                            height: '20px',
                                        }}
                                    />
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
                    disabled={isScraping}
                >
                    {isScraping ? 'Running...' : 'Run Pools Scraping'}
                </button>
                <button
                    type="button"
                    className="button button-secondary"
                    onClick={handleClearAll}
                    disabled={isScraping}
                >
                    Clear All Colors
                </button>
            </div>

            {isScraping && (
                <div className="loader" style={{ marginTop: '10px', textAlign: 'center' }}>
                    Scraping pools... Please wait.
                </div>
            )}

            <div id="scraper-log" className="scraper-log">
                {log.message && <pre>{log.message}</pre>}
            </div>
        </div>
    );
};

export default Colors;