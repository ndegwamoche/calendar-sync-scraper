import React, { useState, useEffect } from 'react';

const Settings = () => {
    const [formData, setFormData] = useState({
        clientId: '',
        clientSecret: '',
        refreshToken: ''
    });
    const [errors, setErrors] = useState({});
    const [message, setMessage] = useState({ text: '', type: '' });
    const [loading, setLoading] = useState(true);

    // Fetch initial credentials when component mounts
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
                        refreshToken: data.data.refresh_token || ''
                    });
                } else {
                    setMessage({ text: 'Failed to load credentials: ' + (data.data?.message || 'Unknown error'), type: 'error' });
                }
            } catch (error) {
                setMessage({ text: `Error loading credentials: ${error.message}`, type: 'error' });
            } finally {
                setLoading(false); // Stop loading
            }
        };

        fetchCredentials();
    }, []);

    // Validation function
    const validateForm = () => {
        const newErrors = {};

        // Validate Client ID
        if (!formData.clientId.trim()) {
            newErrors.clientId = 'Client ID is required.';
        }

        // Validate Client Secret
        if (!formData.clientSecret.trim()) {
            newErrors.clientSecret = 'Client Secret is required.';
        }

        // Validate Refresh Token
        if (!formData.refreshToken.trim()) {
            newErrors.refreshToken = 'Refresh Token is required.';
        }

        setErrors(newErrors);
        setMessage({ text: Object.keys(newErrors).length > 0 ? 'Please fix the errors in the form.' : '', type: 'error' });
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
                ...prev,
                [name]: '',
            }));
        }
        // Clear message when user starts typing
        if (message.text) {
            setMessage({ text: '', type: '' });
        }
    };

    const handleSave = async () => {
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

    return (
        <div id="settings" className="tab-section">
            <h3>Google Settings</h3>
            {loading && <div className="loader">Loading...</div>}
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

            <div className="form-control-group">
                <button type="button" className="button button-primary" onClick={handleSave} disabled={loading}>
                    Save Settings
                </button>
            </div>

            {message.text && (
                <div className={`settings-message ${message.type === 'success' ? 'success-message' : 'error-message'}`}>
                    <pre>{message.text}</pre>
                </div>
            )}
        </div>
    );
};

export default Settings;