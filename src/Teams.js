import { useState, useEffect } from 'react';
import './Teams.scss';

const Teams = () => {
    const [teams, setTeams] = useState([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState(''); // New state for search term

    useEffect(() => {
        const fetchTeams = async () => {
            try {
                const response = await fetch(`${calendarScraperAjax.ajax_url}?action=get_teams`);
                const result = await response.json();
                if (result.success) {
                    setTeams(result.data);
                } else {
                    console.error('Fetch teams failed:', result.data?.message);
                }
            } catch (error) {
                console.error('Error fetching teams:', error);
            }
            setLoading(false);
        };
        fetchTeams();
    }, []);

    const openMediaLibrary = (teamId, teamName) => {
        if (!window.wp?.media) {
            console.error('WordPress media library is not available.');
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
                console.error('Invalid attachment:', attachment);
            }
        });

        mediaFrame.open();
    };

    const uploadImageFromMediaLibrary = async (teamId, teamName, media) => {
        setLoading(true);

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
                console.error('Upload failed:', result.data?.message);
            }
        } catch (error) {
            console.error('Error uploading image:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validateForm()) {
            return;
        }

        setLoading(true);
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
                setMessage({ text: 'Settings saved successfully!', type: 'success' });
            } else {
                setMessage({ text: `Failed to save settings: ${data.data?.message || 'Unknown error'}`, type: 'error' });
            }
        } catch (error) {
            setMessage({ text: `Error saving settings: ${error.message}`, type: 'error' });
        } finally {
            setLoading(false);
        }
    };

    // Filter teams based on search term
    const filteredTeams = teams.filter((team) =>
        team.team_name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div>
            {/* Search Input */}
            <div className="search-container" style={{ margin: '10px' }}>
                <input
                    type="text"
                    placeholder="Search teams by name..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    style={{
                        padding: '0px 8px',
                        width: '100%',
                        fontSize: '14px',
                        borderRadius: '4px',
                        border: '1px solid #ccc',
                    }}
                />
            </div>

            {/* <form onSubmit={handleSubmit}>
                <div className="form-control-group" style={{ marginTop: '10px', marginLeft: '10px' }}>
                    <button type="submit" className="button button-primary" disabled={loading}>
                        Run Teams Scraping
                    </button>
                </div>
            </form> */}

            <div className="team-viewer">
                <table className="team-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Team Name</th>
                            <th>Image</th>
                            <th>Choose Image</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan="6">
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
                                    <td>
                                        <button type="button" onClick={() => openMediaLibrary(team.id, team.team_name)}>
                                            Choose Image
                                        </button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="6">No teams found matching your search.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default Teams;