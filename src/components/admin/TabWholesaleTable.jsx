import React, { useState, useEffect } from 'react';
import Select from 'react-select';

// Wholesale Table Component
const WholesaleTable = ({ categories, isLoading, Settings }) => {
    const [selectedColumns, setSelectedColumns] = useState([]);
    const [tableStyle, setTableStyle] = useState('default');
    const [wholesaleProducts, setWholesaleProducts] = useState('all');
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [productsPerPage, setProductsPerPage] = useState('');
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);

    // Available columns options
    const columnOptions = [
        { value: 'image', label: 'Image' },
        { value: 'product_name', label: 'Product Name' },
        { value: 'price', label: 'Price' },
        { value: 'category', label: 'Category' },
        { value: 'in_stock', label: 'Stock Status' },
        { value: 'quantity', label: 'Quantity' },
        { value: 'sku', label: 'SKU' },
        { value: 'add_to_cart', label: 'Add to Cart' }
    ];

    // Load settings on component mount
    useEffect(() => {
        loadWholesaleTableSettings();
    }, []);

    const loadWholesaleTableSettings = async () => {
        try {
            const response = await fetch('/wp-json/wpt/v1/settings/wholesale-table');
            const data = await response.json();

            // Mock data for demonstration
            const mockSettings = {
                selected_columns: ['image', 'product_name', 'price'],
                table_style: 'default',
                wholesale_products: 'all',
                category: 'all',
                products_per_page: '10'
            };

            setSelectedColumns(data.selected_columns || mockSettings.selected_columns);
            setTableStyle(data.table_style || mockSettings.table_style);
            setWholesaleProducts(data.wholesale_products_opt || mockSettings.wholesale_products);
            setSelectedCategory(data.wholesale_product_category || mockSettings.category);
            setProductsPerPage(data.wholesale_product_pp || mockSettings.products_per_page);
        } catch (error) {
            console.error('Error loading wholesale table settings:', error);
        }
    };

    const handleColumnChange = (columnValue) => {
        setSelectedColumns(prev => {
            if (prev.includes(columnValue)) {
                return prev.filter(col => col !== columnValue);
            } else {
                return [...prev, columnValue];
            }
        });
    };

    const handleSubmit = async () => {
        setSaving(true);
        try {
            const settingsData = {
                selected_columns: selectedColumns,
                table_style: tableStyle,
                wholesale_products_opt: wholesaleProducts,
                wholesale_product_category: selectedCategory,
                wholesale_product_pp: productsPerPage
            };

            const response = await fetch('/wp-json/wpt/v1/settings/wholesale-table', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wpApiSettings?.nonce || 'demo-nonce'
                },
                body: JSON.stringify(settingsData)
            });

            // Mock success response
            setTimeout(() => {
                setMessage('Wholesale table settings saved successfully!');
                setSaving(false);
                setTimeout(() => setMessage(''), 3000);
            }, 1000);

        } catch (error) {
            console.error('Error saving settings:', error);
            setMessage('Error saving settings. Please try again.');
            setSaving(false);
            setTimeout(() => setMessage(''), 3000);
        }
    };

    const selectStyles = {
        control: (provided) => ({
            ...provided,
            padding: '2px 4px',
            border: '1px solid #8c8f94',
            borderRadius: '4px',
            fontSize: '14px',
            minWidth: '200px',
            minHeight: 'auto'
        })
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
                    {/* Column Selection */}
                    <tr className="settings-row">
                        <th className="settings-header">
                            Select Columns to Display
                        </th>
                        <td className="settings-content">
                            <div className="checkbox-group">
                                {columnOptions.map(option => (
                                    <label key={option.value} className="checkbox-label">
                                        <input
                                            type="checkbox"
                                            value={option.value}
                                            checked={selectedColumns.includes(option.value)}
                                            onChange={() => handleColumnChange(option.value)}
                                            className="checkbox-input"
                                        />
                                        {option.label}
                                    </label>
                                ))}
                            </div>
                        </td>
                    </tr>

                    {/* Table Style */}
                    <tr className="settings-row">
                        <th className="settings-header">
                            Select Table Style
                        </th>
                        <td className="settings-content">
                            <div className="radio-group">
                                <label className="radio-label">
                                    <input
                                        type="radio"
                                        name="table_style"
                                        value="default"
                                        checked={tableStyle === 'default'}
                                        onChange={(e) => setTableStyle(e.target.value)}
                                        className="radio-input"
                                    />
                                    Default Style
                                </label>
                                <label className="radio-label">
                                    <input
                                        type="radio"
                                        name="table_style"
                                        value="plugin"
                                        checked={tableStyle === 'plugin'}
                                        onChange={(e) => setTableStyle(e.target.value)}
                                        className="radio-input"
                                    />
                                    Plugin Style
                                </label>
                            </div>
                        </td>
                    </tr>

                    {/* Wholesale Products */}
                    <tr className="settings-row">
                        <th className="settings-header">
                            Select Wholesale Products
                        </th>
                        <td className="settings-content">
                            <div className="radio-group">
                                <label className="radio-label">
                                    <input
                                        type="radio"
                                        name="wholesale_products"
                                        value="all"
                                        checked={wholesaleProducts === 'all'}
                                        onChange={(e) => setWholesaleProducts(e.target.value)}
                                        className="radio-input"
                                    />
                                    All Products
                                </label>
                                <label className="radio-label">
                                    <input
                                        type="radio"
                                        name="wholesale_products"
                                        value="category"
                                        checked={wholesaleProducts === 'category'}
                                        onChange={(e) => setWholesaleProducts(e.target.value)}
                                        className="radio-input"
                                    />
                                    Select a Category
                                </label>
                            </div>
                        </td>
                    </tr>

                    {/* Category Selection */}
                    {wholesaleProducts === 'category' && (
                        <tr className="settings-row">
                            <th className="settings-header">
                                Select a Category as Wholesale to Show on Wholesale Table
                            </th>
                            <td className="settings-content">
                                <Select
                                    isMulti
                                    name="selectedCategories"
                                    options={categories.map(cat => ({ 
                                        value: cat.term_id.toString(), 
                                        label: cat.name 
                                    }))}
                                    className="react-select-container"
                                    classNamePrefix="react-select"
                                    value={
                                        selectedCategory && selectedCategory !== 'all' && selectedCategory !== '' 
                                            ? selectedCategory.split(',').map(catId => {
                                                const category = categories.find(cat => cat.term_id.toString() === catId.toString());
                                                return category ? { 
                                                    value: category.term_id.toString(), 
                                                    label: category.name 
                                                } : null;
                                            }).filter(option => option !== null)
                                            : []
                                    }
                                    onChange={(selected) => {
                                        const selectedIds = selected ? selected.map(option => option.value) : [];
                                        setSelectedCategory(selectedIds.length > 0 ? selectedIds.join(',') : '');
                                    }}
                                    placeholder="Select categories"
                                    isLoading={isLoading || categories.length === 0}
                                    isClearable
                                    closeMenuOnSelect={false}
                                    styles={selectStyles}
                                />
                                <span className="info-text">
                                    Note: If no category is selected, all products will be shown.
                                </span>
                            </td>
                        </tr>
                    )}

                    {/* Products Per Page */}
                    <tr className="settings-row">
                        <th className="settings-header">
                            Products Per Page
                        </th>
                        <td className="settings-content">
                            <input
                                type="text"
                                value={productsPerPage}
                                onChange={(e) => setProductsPerPage(e.target.value)}
                                placeholder="Enter number of products per page"
                                className="text-input"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>

            <div className="button-container">
                <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={saving}
                    className={`save-button ${saving ? 'save-button--loading' : ''}`}
                >
                    {saving ? 'Saving...' : 'Save Wholesale Table Settings'}
                </button>
            </div>
        </div>
    );
};

export default WholesaleTable;