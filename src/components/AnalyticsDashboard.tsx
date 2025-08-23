import React, { useState } from 'react';
import { BarChart3, TrendingUp, TrendingDown, Users, Building, AlertTriangle, CheckCircle, Calendar } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Badge } from './ui/badge';
import { Progress } from './ui/progress';

export function AnalyticsDashboard() {
  const [timeRange, setTimeRange] = useState('last_30_days');

  const complianceData = [
    { category: 'Food Service', compliance: 87, businesses: 45, violations: 12 },
    { category: 'Retail', compliance: 92, businesses: 32, violations: 8 },
    { category: 'Healthcare', compliance: 95, businesses: 18, violations: 3 },
    { category: 'Manufacturing', compliance: 78, businesses: 15, violations: 18 },
    { category: 'Entertainment', compliance: 83, businesses: 22, violations: 9 }
  ];

  const inspectorPerformance = [
    { name: 'Anna Reyes', inspections: 45, accuracy: 98, efficiency: 94 },
    { name: 'Juan Dela Cruz', inspections: 38, accuracy: 94, efficiency: 89 },
    { name: 'Carlos Garcia', inspections: 32, accuracy: 91, efficiency: 92 },
    { name: 'Maria Santos', inspections: 29, accuracy: 96, efficiency: 87 }
  ];

  const violationTrends = [
    { type: 'Fire Safety', current: 23, previous: 28, change: -18 },
    { type: 'Health & Sanitation', current: 45, previous: 41, change: 10 },
    { type: 'Building Safety', current: 12, previous: 15, change: -20 },
    { type: 'Environmental', current: 8, previous: 6, change: 33 }
  ];

  const getComplianceColor = (score: number) => {
    if (score >= 90) return 'text-green-600 bg-green-50';
    if (score >= 80) return 'text-yellow-600 bg-yellow-50';
    return 'text-red-600 bg-red-50';
  };

  const getTrendColor = (change: number) => {
    return change > 0 ? 'text-red-600' : 'text-green-600';
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold">Analytics Dashboard</h2>
          <p className="text-gray-600 text-sm sm:text-base">Compliance trends, performance metrics, and insights</p>
        </div>
        <Select value={timeRange} onValueChange={setTimeRange}>
          <SelectTrigger className="w-full sm:w-48">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="last_7_days">Last 7 Days</SelectItem>
            <SelectItem value="last_30_days">Last 30 Days</SelectItem>
            <SelectItem value="last_quarter">Last Quarter</SelectItem>
            <SelectItem value="last_year">Last Year</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Overview Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Overall Compliance</p>
                <p className="text-2xl font-bold">87%</p>
                <p className="text-xs text-green-600 flex items-center mt-1">
                  <TrendingUp className="h-3 w-3 mr-1" />
                  +3% from last period
                </p>
              </div>
              <CheckCircle className="h-12 w-12 text-green-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Active Violations</p>
                <p className="text-2xl font-bold">88</p>
                <p className="text-xs text-red-600 flex items-center mt-1">
                  <TrendingUp className="h-3 w-3 mr-1" />
                  +5 from last week
                </p>
              </div>
              <AlertTriangle className="h-12 w-12 text-red-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Inspections Completed</p>
                <p className="text-2xl font-bold">247</p>
                <p className="text-xs text-green-600 flex items-center mt-1">
                  <TrendingUp className="h-3 w-3 mr-1" />
                  +12% completion rate
                </p>
              </div>
              <BarChart3 className="h-12 w-12 text-blue-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Revenue Collected</p>
                <p className="text-2xl font-bold">₱2.4M</p>
                <p className="text-xs text-green-600 flex items-center mt-1">
                  <TrendingUp className="h-3 w-3 mr-1" />
                  +8% from fines
                </p>
              </div>
              <Building className="h-12 w-12 text-purple-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Compliance by Business Category */}
      <Card>
        <CardHeader>
          <CardTitle>Compliance by Business Category</CardTitle>
          <CardDescription>Performance breakdown across different business types</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {complianceData.map((item, idx) => (
              <div key={idx} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="flex-1">
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="font-medium">{item.category}</h4>
                    <Badge className={getComplianceColor(item.compliance)}>
                      {item.compliance}% compliant
                    </Badge>
                  </div>
                  <div className="grid grid-cols-3 gap-4 text-sm">
                    <div>
                      <p className="text-gray-500">Businesses</p>
                      <p className="font-medium">{item.businesses}</p>
                    </div>
                    <div>
                      <p className="text-gray-500">Violations</p>
                      <p className="font-medium text-red-600">{item.violations}</p>
                    </div>
                    <div>
                      <p className="text-gray-500">Compliance Rate</p>
                      <Progress value={item.compliance} className="mt-1" />
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Inspector Performance */}
        <Card>
          <CardHeader>
            <CardTitle>Inspector Performance</CardTitle>
            <CardDescription>Top performing inspectors this period</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {inspectorPerformance.map((inspector, idx) => (
                <div key={idx} className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p className="font-medium">{inspector.name}</p>
                    <p className="text-sm text-gray-600">{inspector.inspections} inspections</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm">Accuracy: {inspector.accuracy}%</p>
                    <p className="text-sm">Efficiency: {inspector.efficiency}%</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* Violation Trends */}
        <Card>
          <CardHeader>
            <CardTitle>Violation Trends</CardTitle>
            <CardDescription>Changes in violation types over time</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {violationTrends.map((trend, idx) => (
                <div key={idx} className="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p className="font-medium">{trend.type}</p>
                    <p className="text-sm text-gray-600">Current: {trend.current} violations</p>
                  </div>
                  <div className="text-right">
                    <p className={`text-sm font-medium ${getTrendColor(trend.change)}`}>
                      {trend.change > 0 ? '+' : ''}{trend.change}%
                    </p>
                    <p className="text-xs text-gray-500">vs previous period</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Monthly Compliance Trend Chart Placeholder */}
      <Card>
        <CardHeader>
          <CardTitle>Monthly Compliance Trends</CardTitle>
          <CardDescription>Compliance rates over the past 12 months</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="h-64 flex items-center justify-center border-2 border-dashed border-gray-300 rounded-lg">
            <div className="text-center">
              <BarChart3 className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">Chart visualization would be implemented here</p>
              <p className="text-sm text-gray-500">Using recharts or similar charting library</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}