
const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');

const app = express();
app.use(cors());

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: '',
  database: 'epiguard'
};

app.get('/api/dashboard-stats', async (req, res) => {
  const connection = await mysql.createConnection(dbConfig);

  try {
    // Total patients
    const [patientsRows] = await connection.execute('SELECT COUNT(*) as total FROM patients');

    // Active devices
    const [devicesRows] = await connection.execute("SELECT COUNT(*) as total FROM devices WHERE device_status = 'active'");

    // Alerts last 24h
    const [alertsRows] = await connection.execute(`
      SELECT COUNT(*) as total FROM alerts 
      WHERE event_time >= NOW() - INTERVAL 1 DAY
    `);

    // Seizure events last month
    const [eventsRows] = await connection.execute(`
      SELECT COUNT(*) as total FROM seizure_events 
      WHERE event_time >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    `);

    // Recent alerts details (limit 5)
    const [recentAlerts] = await connection.execute(`
      SELECT p.fullname as patient, a.event_type, a.event_time, a.duration_seconds, a.status
      FROM alerts a
      JOIN patients p ON a.patient_id = p.id
      ORDER BY a.event_time DESC
      LIMIT 5
    `);

    res.json({
      totalPatients: patientsRows[0].total,
      activeDevices: devicesRows[0].total,
      alerts24h: alertsRows[0].total,
      monthlyEvents: eventsRows[0].total,
      recentAlerts: recentAlerts.map(alert => ({
        patient: alert.patient,
        eventType: alert.event_type,
        dateTime: alert.event_time,
        duration: `${Math.floor(alert.duration_seconds / 60)}m ${alert.duration_seconds % 60}s`,
        status: alert.status
      }))
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Database query failed' });
  } finally {
    connection.end();
  }
});

app.listen(3000, () => {
  console.log('Server running on http://localhost:3000');
});
