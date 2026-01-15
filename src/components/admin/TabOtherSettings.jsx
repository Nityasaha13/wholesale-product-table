
import React, { useState, useEffect } from 'react';
import Select from 'react-select';


// Other Settings Component (Empty for now)
const OtherSettings = () => {
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);

    const handleSubmit = async () => {
        setSaving(true);
        // Mock save functionality
        setTimeout(() => {
            setMessage('Other settings saved successfully!');
            setSaving(false);
            setTimeout(() => setMessage(''), 3000);
        }, 1000);
    };

    return (
        <div className="tab-content">
            {message && (
                <div className={`message ${message.includes('Error') ? 'message--error' : 'message--success'}`}>
                    {message}
                </div>
            )}

            <div style={{ textAlign: 'center', padding: '40px', color: '#666' }}>
                <h3>Other Settings</h3>
                <p>This section is currently empty. Additional settings will be added here in the future.</p>
            </div>

            <div className="button-container">
                <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={saving}
                    className={`save-button ${saving ? 'save-button--loading' : ''}`}
                >
                    {saving ? 'Saving...' : 'Save Other Settings'}
                </button>
            </div>
        </div>
    );
};

export default OtherSettings;