import { useState, useEffect } from 'react';
import './Teams.scss';

const Teams = () => {
    const [teams, setTeams] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedColors, setSelectedColors] = useState({}); // Per-team selected colors
    const [errorMessage, setErrorMessage] = useState(''); // For error/success feedback

    useEffect(() => {
        const fetchTeams = async () => {
            try {
                const response = await fetch(`${calendarScraperAjax.ajax_url}?action=get_teams`);
                const result = await response.json();
                if (result.success) {
                    setTeams(result.data.map(team => ({ ...team, color: team.team_color || '' })));
                } else {
                    setErrorMessage(result.data?.message || 'Failed to fetch teams');
                }
            } catch (error) {
                setErrorMessage('Error fetching teams: ' + error.message);
            }
            setLoading(false);
        };
        fetchTeams();
    }, []);

    const openMediaLibrary = (teamId, teamName) => {
        if (!window.wp?.media) {
            setErrorMessage('WordPress media library is not available.');
            return;
        }

        const mediaFrame = wp.media({
            title: 'Select or Upload an Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' },
        });

        mediaFrame.on('select', () => {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            if (attachment?.id && attachment?.url) {
                uploadImageFromMediaLibrary(teamId, teamName, attachment);
            } else {
                setErrorMessage('Invalid attachment selected.');
            }
        });

        mediaFrame.open();
    };

    const uploadImageFromMediaLibrary = async (teamId, teamName, media) => {
        setLoading(true);
        setErrorMessage('');

        const formData = new FormData();
        formData.append('action', 'upload_team_image_from_library');
        formData.append('_ajax_nonce', calendarScraperAjax.nonce);
        formData.append('team_id', teamId);
        formData.append('team_name', teamName);
        formData.append('media_id', media.id);

        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();
            if (result.success) {
                setTeams(teams.map((team) =>
                    team.id === teamId ? { ...team, image_url: result.data.image_url } : team
                ));
            } else {
                setErrorMessage(result.data?.message || 'Failed to upload image');
            }
        } catch (error) {
            setErrorMessage('Error uploading image: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const updateTeamColor = async (teamId, color, team_name) => {
        setLoading(true);
        setErrorMessage('');

        const formData = new FormData();
        formData.append('action', 'update_team_color');
        formData.append('_ajax_nonce', calendarScraperAjax.nonce);
        formData.append('team_id', teamId);
        formData.append('color', color);
        formData.append('team_name', team_name);

        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                body: formData,
            });
            const result = await response.json();
            if (result.success) {
                setTeams(teams.map((team) =>
                    team.id === teamId ? { ...team, color } : team
                ));
                setSelectedColors((prev) => ({ ...prev, [teamId]: undefined })); // Clear selected color
                setErrorMessage('Team color updated successfully!');
            } else {
                setErrorMessage(result.data?.message || 'Failed to update color');
            }
        } catch (error) {
            setErrorMessage('Error updating color: ' + error.message);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrorMessage('');
        try {
            const response = await fetch(calendarScraperAjax.ajax_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'run_all_teams_scraper',
                    _ajax_nonce: calendarScraperAjax.nonce,
                    season: 42024,
                    link_structure: "https://www.bordtennisportalen.dk/DBTU/HoldTurnering/Stilling/#2,{season},{pool},{group},{region},,,,",
                }),
            });
            const data = await response.json();
            if (data.success) {
                setErrorMessage('Settings saved successfully!');
            } else {
                setErrorMessage(`Failed to save settings: ${data.data?.message || 'Unknown error'}`);
            }
        } catch (error) {
            setErrorMessage(`Error saving settings: ${error.message}`);
        } finally {
            setLoading(false);
        }
    };

    const filteredTeams = teams.filter((team) =>
        team.team_name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div>

            <form onSubmit={handleSubmit}>
                <div className="form-control-group" style={{ marginTop: '10px', marginLeft: '10px' }}>
                    <button type="submit" className="button button-primary" disabled={loading}>
                        Run Teams Scraping
                    </button>
                </div>
            </form>

            {/* Error Message Display */}
            {errorMessage && (
                <div className="error-message">
                    {errorMessage}
                </div>
            )}

            <div className="search-container" style={{ margin: '10px' }}>
                <input
                    type="text"
                    placeholder="Search teams by name..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    style={{
                        padding: '8px',
                        width: '100%',
                        fontSize: '14px',
                        borderRadius: '4px',
                        border: '1px solid #ccc',
                    }}
                />
            </div>

            <div className="team-viewer">
                <table className="team-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Team Name</th>
                            <th>Image</th>
                            <th>Choose Image</th>
                            <th>Team Color</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan="5">
                                    <div className="loader">Loading...</div>
                                </td>
                            </tr>
                        ) : filteredTeams.length > 0 ? (
                            filteredTeams.map((team) => (
                                <tr key={team.id}>
                                    <td>{team.team_value}</td>
                                    <td>{team.team_name}</td>
                                    <td>
                                        {team.image_url ? (
                                            <img
                                                src={team.image_url}
                                                alt={team.team_name}
                                                style={{ width: '100px', height: '70px', borderRadius: '4px' }}
                                            />
                                        ) : (
                                            <em>No Image</em>
                                        )}
                                    </td>
                                    <td style={{ textAlign: 'center' }}>
                                        <button
                                            type="button"
                                            className="button button-primary"
                                            onClick={() => openMediaLibrary(team.id, team.team_name)}
                                            style={{ padding: '0px 10px' }}
                                        >
                                            Choose Image
                                        </button>
                                    </td>
                                    <td style={{ display: 'flex', alignItems: 'center', gap: '10px', justifyContent: 'center' }}>
                                        <input
                                            type="color"
                                            value={selectedColors[team.id] || team.color || '#039be5'}
                                            onChange={(e) => setSelectedColors({ ...selectedColors, [team.id]: e.target.value })}
                                            style={{ width: '50px', height: '40px', cursor: 'pointer', marginRight: '10px' }}
                                        />
                                        <button
                                            type="button"
                                            className="button button-primary"
                                            onClick={() => updateTeamColor(team.id, selectedColors[team.id] || team.color || '#039be5', team.team_name)}
                                            title="Assign selected color to team"
                                            aria-label="Assign color"
                                            style={{ padding: '0px 10px' }}
                                        >
                                            Assign
                                        </button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="5">No teams found matching your search.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default Teams;