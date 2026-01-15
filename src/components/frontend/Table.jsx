import React, { useState, useEffect } from 'react';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Paper from '@mui/material/Paper';


const TableData = () => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await fetch('/wp-json/react-base/v1/table-data');
                const json = await response.json();
                setData(json);
                setLoading(false);
            } catch (error) {
                console.error("Error fetching table data:", error);
                setLoading(false);
            }
        };

        fetchData();
    }, []);

    if (loading) {
        return <p>Loading table data...</p>;
    }

    if (!data || !data.columns || !data.rows) {
        return <p>No data available.</p>;
    }

    return (
        <div className="frontend-table">
            <h2>Dessert Nutrition Table</h2>
            <TableContainer component={Paper}>
                <Table sx={{ minWidth: 650 }} aria-label="simple table">
                    <TableHead>
                        <TableRow>
                            {data.columns.map((col, index) => (
                                <TableCell key={index}>{col}</TableCell>
                            ))}
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {data.rows.map((row, rowIndex) => (
                            <TableRow key={rowIndex}>
                                {row.map((cell, cellIndex) => (
                                    <TableCell key={cellIndex}>{cell}</TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </TableContainer>
        </div>
    );
};

export default TableData;
