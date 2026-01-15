import React, { useState, useEffect } from 'react';
import Select from 'react-select';


// Discount Rules Component
const DiscountRules = ({ categories, isLoading }) => {
    const [EnableDiscount, setEnableDiscount] = useState('wholesale_discount_off');
    const [wholesaleDiscountType, setWholesaleDiscountType] = useState('percentage');
    const [wholesaleDiscountValue, setWholesaleDiscountValue] = useState('');
    const [includeCategories, setIncludeCategories] = useState([]);
    const [excludeCategories, setExcludeCategories] = useState([]);
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);

    // Load settings on component mount
    useEffect(() => {
        loadDiscountSettings();
    }, []);

    const loadDiscountSettings = async () => {
        try {
            const response = await fetch('/wp-json/wpt/v1/settings/discount-rules');
            const data = await response.json();

            // Parse categories
            let excludeCategories = [];
            if (data.wpt_exclude_categories) {
                if (typeof data.wpt_exclude_categories === 'string' && data.wpt_exclude_categories.trim()) {
                    excludeCategories = data.wpt_exclude_categories.split(',')
                        .map(id => id.trim())
                        .filter(id => id);
                } else if (Array.isArray(data.wpt_exclude_categories)) {
                    excludeCategories = data.wpt_exclude_categories.map(id => id.toString());
                }
            }

            let includeCategories = [];
            if (data.wpt_include_categories) {
                if (typeof data.wpt_include_categories === 'string' && data.wpt_include_categories.trim()) {
                    includeCategories = data.wpt_include_categories.split(',')
                        .map(id => id.trim())
                        .filter(id => id);
                } else if (Array.isArray(data.wpt_include_categories)) {
                    includeCategories = data.wpt_include_categories.map(id => id.toString());
                }
            }

            setEnableDiscount(data.wpt_enabled_wholesale_discount || 'wholesale_discount_off');
            setWholesaleDiscountType(data.wpt_wholesale_discount_type || 'percentage');
            setWholesaleDiscountValue(data.wpt_wholesale_discount_value || '');
            setIncludeCategories(includeCategories);
            setExcludeCategories(excludeCategories);
        } catch (error) {
            console.error('Error loading discount settings:', error);
        }
    };

    const handleSubmit = async () => {
        setSaving(true);
        try {
            const settingsData = {
                wpt_enabled_wholesale_discount: EnableDiscount,
                wpt_wholesale_discount_type: wholesaleDiscountType,
                wpt_wholesale_discount_value: wholesaleDiscountValue,
                wpt_include_categories: includeCategories.join(','),
                wpt_exclude_categories: excludeCategories.join(',')
            };

            const response = await fetch('/wp-json/wpt/v1/settings/discount-rules', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings?.nonce || 'demo-nonce'
                },
                body: JSON.stringify(settingsData)
            });

            // Mock success response
            setTimeout(() => {
                setMessage('Discount rules saved successfully!');
                setSaving(false);
                setTimeout(() => setMessage(''), 3000);
            }, 1000);

        } catch (error) {
            console.error('Error saving discount settings:', error);
            setMessage('Error saving discount settings. Please try again.');
            setSaving(false);
            setTimeout(() => setMessage(''), 3000);
        }
    };

    return (
        <div className="tab-content">
            {message && (
                <div className={`message ${message.includes('Error') ? 'message--error' : 'message--success'}`}>
                    {message}
                </div>
            )}

            <table className="settings-table">
                <tbody>
                    {/* Wholesale discount */}
                    <tr className="settings-row">
                        <th className="settings-header">
                            Wholesale Discount ?
                        </th>
                        <td className="settings-content">
                            <label className="checkbox-label">
                                <input
                                    type="checkbox"
                                    value={EnableDiscount}
                                    checked={EnableDiscount === 'wholesale_discount_on'}
                                    onChange={() => setEnableDiscount(EnableDiscount === 'wholesale_discount_on' ? 'wholesale_discount_off' : 'wholesale_discount_on')}
                                    className="checkbox-input"
                                />
                                Enable Wholesale Discount
                            </label>
                        </td>
                    </tr>

                    {EnableDiscount === 'wholesale_discount_on' && (
                    <>
                        <tr className="settings-row">
                            <th className="settings-header">
                                Wholesale Discount Type
                            </th>
                            <td className="settings-content">
                                <select
                                    value={wholesaleDiscountType} 
                                    onChange={(e) => setWholesaleDiscountType(e.target.value)}
                                    className="select-input"
                                >
                                    <option value="percentage">Percentage</option> 
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </td>
                        </tr>

                        <tr className="settings-row">
                            <th className="settings-header">
                                Wholesale Discount Value
                            </th>
                            <td className="settings-content">
                                <input
                                    type="text"
                                    value={wholesaleDiscountValue}
                                    onChange={(e) => setWholesaleDiscountValue(e.target.value)}
                                    placeholder="Enter discount value"
                                    className="text-input"
                                />
                            </td>
                        </tr>

                        <tr className="settings-row">
                            <th className="settings-header">
                                Include Categories for Discount
                            </th>
                            <td className="settings-content">
                                <Select
                                    isMulti
                                    name="includeCategories"
                                    options={categories.map(cat => ({ 
                                        value: cat.term_id.toString(), 
                                        label: cat.name 
                                    }))}
                                    className="react-select-container"
                                    classNamePrefix="react-select"
                                    value={
                                        (Array.isArray(includeCategories) && categories.length > 0) 
                                            ? includeCategories
                                                .map(catId => {
                                                    const category = categories.find(cat => cat.term_id.toString() === catId.toString());
                                                    return category ? { 
                                                        value: category.term_id.toString(), 
                                                        label: category.name 
                                                    } : null;
                                                })
                                                .filter(option => option !== null)
                                            : []
                                    }
                                    onChange={(selected) => {
                                        const selectedIds = selected ? selected.map(option => option.value.toString()) : [];
                                        setIncludeCategories(selectedIds);
                                    }}
                                    placeholder="Select categories to include"
                                    isLoading={isLoading || categories.length === 0}
                                />
                                <span className="info-text">
                                    Note: If no categories are selected, all products will be eligible for the discount.
                                </span>
                            </td>
                        </tr>

                        <tr className="settings-row">
                            <th className="settings-header">
                                Exclude Categories from Discount
                            </th>
                            <td className="settings-content">
                                <Select
                                    isMulti
                                    name="excludeCategories"
                                    options={categories.map(cat => ({ 
                                        value: cat.term_id.toString(), 
                                        label: cat.name 
                                    }))}
                                    className="react-select-container"
                                    classNamePrefix="react-select"
                                    value={
                                        (Array.isArray(excludeCategories) && categories.length > 0) 
                                            ? excludeCategories
                                                .map(catId => {
                                                    const category = categories.find(cat => cat.term_id.toString() === catId.toString());
                                                    return category ? { 
                                                        value: category.term_id.toString(), 
                                                        label: category.name 
                                                    } : null;
                                                })
                                                .filter(option => option !== null)
                                            : []
                                    }
                                    onChange={(selected) => {
                                        const selectedIds = selected ? selected.map(option => option.value.toString()) : [];
                                        setExcludeCategories(selectedIds);
                                    }}
                                    placeholder="Select categories to exclude"
                                    isLoading={isLoading || categories.length === 0}
                                />
                            </td>
                        </tr>
                    </>
                    )}
                </tbody>
            </table>

            <div className="button-container">
                <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={saving}
                    className={`save-button ${saving ? 'save-button--loading' : ''}`}
                >
                    {saving ? 'Saving...' : 'Save Discount Rules'}
                </button>
            </div>
        </div>
    );
};

export default DiscountRules;