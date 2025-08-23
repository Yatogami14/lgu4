import React, { useState } from 'react';
import { User, Star, TrendingUp, AlertTriangle, Clock } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { InspectorCard } from './inspector/InspectorCard';
import { InspectorDetailsModal } from './inspector/InspectorDetailsModal';
import { mockInspectors, Inspector } from './constants/inspectorData';
import { filterInspectors, calculateInspectorStats } from './utils/inspectorUtils';

export function InspectorDashboard() {
  const [inspectors, setInspectors] = useState<Inspector[]>(mockInspectors);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedInspector, setSelectedInspector] = useState<Inspector | null>(null);
  const [statusFilter, setStatusFilter] = useState('all');

  const filteredInspectors = filterInspectors(inspectors, searchTerm, statusFilter);
  const stats = calculateInspectorStats(inspectors);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold">Inspector Management</h2>
          <p className="text-gray-600 text-sm sm:text-base">Manage inspector profiles, certifications, and performance</p>
        </div>
        <Button className="w-full sm:w-auto">
          <User className="h-4 w-4 mr-2" />
          Add Inspector
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Inspectors</p>
                <p className="text-2xl font-bold">{stats.totalInspectors}</p>
              </div>
              <User className="h-12 w-12 text-blue-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Active Inspectors</p>
                <p className="text-2xl font-bold">{stats.activeInspectors}</p>
              </div>
              <Star className="h-12 w-12 text-green-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Avg Performance</p>
                <p className="text-2xl font-bold">{stats.avgPerformance}%</p>
              </div>
              <TrendingUp className="h-12 w-12 text-purple-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Expiring Certs</p>
                <p className="text-2xl font-bold">{stats.expiringCertifications}</p>
              </div>
              <AlertTriangle className="h-12 w-12 text-yellow-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1 min-w-0">
              <Input
                placeholder="Search by name, department, or specialization..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full"
              />
            </div>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md w-full sm:w-auto"
            >
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="on_leave">On Leave</option>
            </select>
          </div>
        </CardContent>
      </Card>

      {/* Inspector List */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {filteredInspectors.map((inspector) => (
          <InspectorCard
            key={inspector.id}
            inspector={inspector}
            onClick={setSelectedInspector}
          />
        ))}
        {filteredInspectors.length === 0 && (
          <Card className="lg:col-span-2">
            <CardContent className="text-center py-12">
              <User className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No inspectors found matching your search criteria.</p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Inspector Details Modal */}
      <InspectorDetailsModal
        inspector={selectedInspector}
        onClose={() => setSelectedInspector(null)}
      />
    </div>
  );
}