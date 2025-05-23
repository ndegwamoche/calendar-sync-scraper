import React, { useState } from 'react';
import './Logs.scss';

const Logs = ({ logInfo }) => {
    const [expandedRows, setExpandedRows] = useState({});

    // Toggle expand/collapse for a row
    const toggleExpand = (id) => {
        setExpandedRows((prev) => ({
            ...prev,
            [id]: !prev[id],
        }));
    };

    // Truncate text to a specified length
    const truncateText = (text, maxLength = 20) => {
        if (!text) return '';
        return text.length > maxLength ? `${text.substring(0, maxLength)}...` : text;
    };

    // Parse details if it's JSON (e.g., match data)
    const parseDetails = (details) => {
        if (!details || !details.startsWith('[')) {
            return { isJson: false, content: details };
        }
        try {
            const parsed = JSON.parse(details);
            if (Array.isArray(parsed)) {
                return { isJson: true, matches: parsed, summary: `Found ${parsed.length} matches` };
            }
            return { isJson: false, content: details };
        } catch (e) {
            console.error('Failed to parse details as JSON:', e);
            return { isJson: false, content: details };
        }
    };

    return (
        <div className="log-viewer">
            <table className="log-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Start Datetime</th>
                        <th>End Datetime</th>
                        <th>Dur (s)</th>
                        <th>Records</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    {logInfo.length === 0 ? (
                        <tr>
                            <td colSpan="6">
                                <div className="loader">Loading...</div>
                            </td>
                        </tr>
                    ) : (
                        logInfo.map((log, index) => {
                            const detailsData = parseDetails(log.details);
                            const isExpanded = expandedRows[log.id || index];
                            return (
                                <React.Fragment key={log.id || index}>
                                    <tr
                                        className={`log-row ${log.status} ${isExpanded ? 'expanded' : ''}`}
                                        onClick={() => toggleExpand(log.id || index)}
                                    >
                                        <td>{log.status === 'completed' ? '✅ Completed' : '❌ Failed'}</td>
                                        <td>{log.start_datetime}</td>
                                        <td>{log.close_datetime || 'N/A'}</td>
                                        <td>{log.duration !== null ? log.duration : 'N/A'}</td>
                                        <td>{log.total_matches}</td>
                                        <td>
                                            {detailsData.isJson
                                                ? truncateText(detailsData.summary, 20)
                                                : truncateText(detailsData.content, 20)}
                                        </td>
                                    </tr>
                                    {isExpanded && detailsData.isJson && (
                                        <tr className="log-details">
                                            <td colSpan="6">
                                                <ul className="match-list">
                                                    {detailsData.matches.map((match, matchIndex) => (
                                                        <li key={matchIndex}>
                                                            Match {match.tid}: {match.hjemmehold} vs {match.udehold} at {match.spillested} - {match.resultat}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </td>
                                        </tr>
                                    )}
                                    {isExpanded && !detailsData.isJson && detailsData.content && (
                                        <tr className="log-details">
                                            <td colSpan="6">
                                                <pre>{detailsData.content}</pre>
                                            </td>
                                        </tr>
                                    )}
                                </React.Fragment>
                            );
                        })
                    )}
                </tbody>
            </table>
        </div>
    );
};

export default Logs;