import React, { useState, useEffect } from 'react';
import Select from 'react-select';
import WholesaleTable from './TabWholesaleTable';
import DiscountRules from './TabDiscountRules';
import OtherSettings from './TabOtherSettings';


// Main Dashboard Component
const Dashboard = () => {
    const [activeTab, setActiveTab] = useState('wholesale-table');
    const [categories, setCategories] = useState([]);
    const [isLoading, setIsLoading] = useState(true);

    const tabs = [
        { id: 'wholesale-table', label: 'Wholesale Table' },
        { id: 'discount-rules', label: 'Discount Rules' },
        { id: 'other', label: 'Other' }
    ];

    useEffect(() => {
        loadCategories();
    }, []);

    const loadCategories = async () => {
        try {
            const response = await fetch('/wp-json/wpt/v1/categories');
            const data = await response.json();
            
            if (data && data.length) {
                setCategories(data);
            } else if (data && Object.values(data).length) {
                const categoriesArray = Object.values(data); 
                setCategories(categoriesArray);
            } else {
                setCategories([]);
            }
        } catch (error) {
            console.error('Error loading categories:', error);
            // Mock categories for demonstration
            setCategories([
                { term_id: 1, name: 'Electronics' },
                { term_id: 2, name: 'Clothing' },
                { term_id: 3, name: 'Books' }
            ]);
        } finally {
            setIsLoading(false);
        }
    };

    const renderTabContent = () => {
        switch (activeTab) {
            case 'wholesale-table':
                return <WholesaleTable categories={categories} isLoading={isLoading} />;
            case 'discount-rules':
                return <DiscountRules categories={categories} isLoading={isLoading} />;
            case 'other':
                return <OtherSettings />;
            default:
                return <WholesaleTable categories={categories} isLoading={isLoading} />;
        }
    };

    return (
        <div className="admin-dashboard">
            <div className="card-board">
                <h1 className="dashboard-title">
                    Wholesale Product Table Settings
                </h1>

                {/* Tabs Navigation */}
                <div className="tabs-navigation">
                    {tabs.map(tab => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`tab-button ${activeTab === tab.id ? 'tab-button--active' : ''}`}
                            style={{
                                backgroundColor: activeTab === tab.id ? '#0073aa' : 'transparent',
                                color: activeTab === tab.id ? '#fff' : '#0073aa',
                                borderBottom: activeTab === tab.id ? '1px solid #0073aa' : '1px solid transparent'
                            }}
                            onMouseOver={(e) => {
                                if (activeTab !== tab.id) {
                                    e.target.style.backgroundColor = '#f0f8ff';
                                }
                            }}
                            onMouseOut={(e) => {
                                if (activeTab !== tab.id) {
                                    e.target.style.backgroundColor = 'transparent';
                                }
                            }}
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Tab Content */}
                <div className="settings-container">
                    {renderTabContent()}
                </div>
            </div>
        </div>
    );
};

export default Dashboard;