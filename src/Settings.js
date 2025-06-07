import React, { useState, useEffect } from 'react';

const Settings = () => {
    const [formData, setFormData] = useState({
        clientId: '',
        clientSecret: '',
        refreshToken: '',
        timeOffset: '+1 hour'
    });
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState({ text: '', type: '' });
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchCredentials = async () => {
            setLoading(true);
            try {
                const response = await fetch(`${calendarScraperAjax.ajax_url}?action=get_google_credentials&_ajax_nonce=${calendarScraperAjax.nonce}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await response.json();
                if (data.success && data.data) {
                    setFormData({
                        clientId: data.data.client_id || '',
                        clientSecret: data.data.client_secret || '',
                        refreshToken: data.data.refresh_token || '',
                        timeOffset: data.data.time_offset || '+1 hour'
                    });
                } else {
                    setMessage({ text: 'Failed to load credentials: ' + (data.data?.message || 'Unknown error'), type: 'error' });
                }
            } catch (error) {
                setMessage({ text: `Error loading credentials: ${error.message}`, type: 'error' });
            } finally {
                setLoading(false);
            }
        };

        fetchCredentials();
    }, []);

    const validateForm = () => {
        const newErrors = {};

        if (!formData.clientId.trim()) {
            newErrors.clientId = 'Client ID is required.';
        }

        if (!formData.clientSecret.trim()) {
            newErrors.clientSecret = 'Client Secret is required.';
        }

        if (!formData.refreshToken.trim()) {
            newErrors.refreshToken = 'Refresh Token is required.';
        }

        if (!formData.timeOffset) {
            newErrors.timeOffset = 'Time offset is required.';
        }

        setErrors(newErrors);
        setMessage({ text: Object.keys(newErrors).length > 0 ? 'Please fix the errors in the form.' : '', type: 'error' });
        return Object.keys(newErrors).length === 0;
    };

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
        if (message.text) {
            setMessage({ text: '', type: '' });
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
                    action: 'save_google_credentials',
                    _ajax_nonce: calendarScraperAjax.nonce,
                    client_id: formData.clientId,
                    client_secret: formData.clientSecret,
                    refresh_token: formData.refreshToken,
                    time_offset: formData.timeOffset,
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

    const timeOffsetOptions = ['+1 hour', '+2 hours', '+3 hours', '+4 hours', '+5 hours', '+6 hours', '+7 hours'];

    return (
        <div id="settings" className="tab-section">
            {loading && <div className="loader">Loading...</div>}

            <form onSubmit={handleSubmit}>
                <fieldset className="settings-group">
                    <legend>General Settings</legend>
                    <div className="form-section">
                        <div className="form-control-group">
                            <label htmlFor="time-offset" className="form-label">Event Duration:</label>
                            <select
                                id="time-offset"
                                name="timeOffset"
                                className={`form-input ${errors.timeOffset ? 'has-error' : ''}`}
                                value={formData.timeOffset}
                                onChange={handleInputChange}
                            >
                                {timeOffsetOptions.map((option) => (
                                    <option key={option} value={option}>
                                        {option}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {errors.timeOffset && <span className="error-message">{errors.timeOffset}</span>}
                    </div>
                </fieldset>

                <fieldset className="settings-group">
                    <legend>Google Services</legend>
                    <div className="form-section">
                        <div className="form-control-group">
                            <label htmlFor="client-id" className="form-label">Client ID:</label>
                            <input
                                type="text"
                                id="client-id"
                                name="clientId"
                                className={`form-input ${errors.clientId ? 'has-error' : ''}`}
                                value={formData.clientId}
                                onChange={handleInputChange}
                                placeholder="Enter Google Client ID"
                            />
                        </div>
                        {errors.clientId && <span className="error-message">{errors.clientId}</span>}
                    </div>

                    <div className="form-section">
                        <div className="form-control-group">
                            <label htmlFor="client-secret" className="form-label">Client Secret:</label>
                            <input
                                type="text"
                                id="client-secret"
                                name="clientSecret"
                                className={`form-input ${errors.clientSecret ? 'has-error' : ''}`}
                                value={formData.clientSecret}
                                onChange={handleInputChange}
                                placeholder="Enter Google Client Secret"
                            />
                        </div>
                        {errors.clientSecret && <span className="error-message">{errors.clientSecret}</span>}
                    </div>

                    <div className="form-section">
                        <div className="form-control-group">
                            <label htmlFor="refresh-token" className="form-label">Refresh Token:</label>
                            <input
                                type="text"
                                id="refresh-token"
                                name="refreshToken"
                                className={`form-input ${errors.refreshToken ? 'has-error' : ''}`}
                                value={formData.refreshToken}
                                onChange={handleInputChange}
                                placeholder="Enter Google Refresh Token"
                            />
                        </div>
                        {errors.refreshToken && <span className="error-message">{errors.refreshToken}</span>}
                    </div>
                </fieldset>

                <div className="form-control-group">
                    <button type="submit" className="button button-primary" disabled={loading}>
                        Save Settings
                    </button>
                </div>
            </form>

            {message.text && (
                <div className={`settings-message ${message.type === 'success' ? 'success-message' : 'error-message'}`}>
                    <pre>{message.text}</pre>
                </div>
            )}
        </div>
    );
};

export default Settings;