import React, { useState } from 'react';

const Settings = () => {

    return (
        <div id="settings" className="tab-section">
            <div className="form-section">
                <div className="form-control-group">
                    <label htmlFor="pool-select-color" className="form-label">API Key:</label>
                    <input
                        type="text"
                        id="link-structure"
                        name="linkStructure"
                        className={`form-input`}
                        placeholder="Google calender API key"
                    />
                </div>
            </div>
        </div>
    )
}

export default Settings;
