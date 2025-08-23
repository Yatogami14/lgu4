import React, { useState } from 'react';
import { Building, TrendingUp, AlertTriangle, CheckCircle, Calendar, MapPin, Phone, Mail, FileText, Star, Activity } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Badge } from './ui/badge';
import { Progress } from './ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { Avatar, AvatarFallback } from './ui/avatar';

interface Business {
  id: string;
  name: string;
  address: string;
  phone: string;
  email: string;
  businessType: string;
  registrationNumber: string;
  permitExpiry: string;
  complianceScore: number;
  status: 'excellent' | 'good' | 'fair' | 'poor' | 'critical';
  lastInspection: string;
  totalInspections: number;
  violations: number;
  resolvedViolations: number;
  outstandingFines: number;
  riskLevel: 'low' | 'medium' | 'high';
  certifications: string[];
  inspectionHistory: InspectionRecord[];
}

interface InspectionRecord {
  id: string;
  date: string;
  type: string;
  inspector: string;
  score: number;
  violations: number;
  status: 'passed' | 'failed' | 'conditional';
}

const mockBusinesses: Business[] = [
  {
    id: '1',
    name: 'ABC Restaurant',
    address: '123 Main St, Makati City',
    phone: '+63 2 8123 4567',
    email: 'manager@abcrestaurant.com',
    businessType: 'Food Service',
    registrationNumber: 'DTI-2024-001',
    permitExpiry: '2024-12-31',
    complianceScore: 85,
    status: 'good',
    lastInspection: '2024-01-10',
    totalInspections: 12,
    violations: 3,
    resolvedViolations: 2,
    outstandingFines: 15000,
    riskLevel: 'medium',
    certifications: ['Food Safety Certification', 'Sanitation Permit'],
    inspectionHistory: [
      {
        id: '1',
        date: '2024-01-10',
        type: 'Health & Sanitation',
        inspector: 'Juan Dela Cruz',
        score: 85,
        violations: 1,
        status: 'conditional'
      },
      {
        id: '2',
        date: '2023-12-15',
        type: 'Fire Safety',
        inspector: 'Anna Reyes',
        score: 90,
        violations: 0,
        status: 'passed'
      }
    ]
  },
  {
    id: '2',
    name: 'XYZ Mall',
    address: '456 Commerce Ave, BGC',
    phone: '+63 2 8987 6543',
    email: 'operations@xyzmall.com',
    businessType: 'Shopping Center',
    registrationNumber: 'SEC-2024-002',
    permitExpiry: '2024-11-30',
    complianceScore: 72,
    status: 'fair',
    lastInspection: '2024-01-12',
    totalInspections: 8,
    violations: 5,
    resolvedViolations: 3,
    outstandingFines: 75000,
    riskLevel: 'high',
    certifications: ['Fire Safety Certificate', 'Building Permit'],
    inspectionHistory: [
      {
        id: '1',
        date: '2024-01-12',
        type: 'Fire Safety',
        inspector: 'Anna Reyes',
        score: 72,
        violations: 2,
        status: 'failed'
      }
    ]
  },
  {
    id: '3',
    name: 'Tech Hub Office',
    address: '789 IT Park, Cebu',
    phone: '+63 32 123 4567',
    email: 'admin@techhub.com',
    businessType: 'Office Building',
    registrationNumber: 'BIR-2024-003',
    permitExpiry: '2025-01-31',
    complianceScore: 95,
    status: 'excellent',
    lastInspection: '2024-01-08',
    totalInspections: 6,
    violations: 1,
    resolvedViolations: 1,
    outstandingFines: 0,
    riskLevel: 'low',
    certifications: ['Building Safety Certificate', 'Environmental Clearance'],
    inspectionHistory: [
      {
        id: '1',
        date: '2024-01-08',
        type: 'Building Safety',
        inspector: 'Carlos Garcia',
        score: 95,
        violations: 0,
        status: 'passed'
      }
    ]
  }
];

export function BusinessCompliance() {
  const [businesses, setBusinesses] = useState<Business[]>(mockBusinesses);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedBusiness, setSelectedBusiness] = useState<Business | null>(null);
  const [filterStatus, setFilterStatus] = useState('all');

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'excellent': return 'bg-green-100 text-green-800';
      case 'good': return 'bg-blue-100 text-blue-800';
      case 'fair': return 'bg-yellow-100 text-yellow-800';
      case 'poor': return 'bg-orange-100 text-orange-800';
      case 'critical': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getRiskColor = (risk: string) => {
    switch (risk) {
      case 'low': return 'bg-green-100 text-green-800';
      case 'medium': return 'bg-yellow-100 text-yellow-800';
      case 'high': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getInspectionStatusColor = (status: string) => {
    switch (status) {
      case 'passed': return 'bg-green-100 text-green-800';
      case 'conditional': return 'bg-yellow-100 text-yellow-800';
      case 'failed': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const filteredBusinesses = businesses.filter(business => {
    const matchesSearch = business.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         business.businessType.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         business.address.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = filterStatus === 'all' || business.status === filterStatus;
    
    return matchesSearch && matchesStatus;
  });

  const totalBusinesses = businesses.length;
  const excellentBusinesses = businesses.filter(b => b.status === 'excellent').length;
  const avgComplianceScore = Math.round(businesses.reduce((sum, b) => sum + b.complianceScore, 0) / businesses.length);
  const highRiskBusinesses = businesses.filter(b => b.riskLevel === 'high').length;

  const renderBusinessCard = (business: Business) => (
    <Card key={business.id} className="hover:shadow-md transition-shadow cursor-pointer"
          onClick={() => setSelectedBusiness(business)}>
      <CardContent className="pt-6">
        <div className="flex items-start justify-between mb-4">
          <div className="flex-1">
            <div className="flex items-center space-x-3 mb-2">
              <h3 className="font-semibold text-lg">{business.name}</h3>
              <Badge className={getStatusColor(business.status)}>
                {business.status}
              </Badge>
              <Badge className={getRiskColor(business.riskLevel)}>
                {business.riskLevel} risk
              </Badge>
            </div>
            <p className="text-gray-600 mb-2">{business.businessType}</p>
            <p className="text-sm text-gray-500 flex items-center mb-3">
              <MapPin className="h-4 w-4 mr-1" />
              {business.address}
            </p>
            
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p className="text-gray-500">Last Inspection</p>
                <p className="font-medium">{new Date(business.lastInspection).toLocaleDateString()}</p>
              </div>
              <div>
                <p className="text-gray-500">Total Inspections</p>
                <p className="font-medium">{business.totalInspections}</p>
              </div>
              <div>
                <p className="text-gray-500">Violations</p>
                <p className="font-medium text-red-600">{business.violations}</p>
              </div>
              <div>
                <p className="text-gray-500">Outstanding Fines</p>
                <p className="font-medium text-red-600">₱{business.outstandingFines.toLocaleString()}</p>
              </div>
            </div>
          </div>
          
          <div className="text-center ml-4">
            <div className="mb-3">
              <p className="text-sm text-gray-500">Compliance Score</p>
              <div className="text-3xl font-bold text-green-600">{business.complianceScore}%</div>
            </div>
            <Progress value={business.complianceScore} className="w-20" />
          </div>
        </div>
        
        <div className="border-t pt-3 mt-3">
          <p className="text-xs text-gray-500 mb-2">Certifications</p>
          <div className="flex flex-wrap gap-1">
            {business.certifications.slice(0, 2).map((cert, idx) => (
              <Badge key={idx} variant="outline" className="text-xs">
                {cert}
              </Badge>
            ))}
            {business.certifications.length > 2 && (
              <Badge variant="outline" className="text-xs">
                +{business.certifications.length - 2} more
              </Badge>
            )}
          </div>
          <p className="text-xs text-gray-500 mt-2">
            Permit expires: {new Date(business.permitExpiry).toLocaleDateString()}
          </p>
        </div>
      </CardContent>
    </Card>
  );

  const renderBusinessDetails = () => {
    if (!selectedBusiness) return null;

    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <Card className="w-full max-w-6xl max-h-[90vh] overflow-y-auto">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-2xl">{selectedBusiness.name}</CardTitle>
                <CardDescription>{selectedBusiness.businessType} • {selectedBusiness.address}</CardDescription>
              </div>
              <Button variant="outline" onClick={() => setSelectedBusiness(null)}>
                Close
              </Button>
            </div>
          </CardHeader>
          <CardContent className="space-y-6">
            <Tabs defaultValue="overview" className="w-full">
              <TabsList className="grid w-full grid-cols-4">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="inspections">Inspection History</TabsTrigger>
                <TabsTrigger value="violations">Violations</TabsTrigger>
                <TabsTrigger value="certifications">Certifications</TabsTrigger>
              </TabsList>

              <TabsContent value="overview" className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                  <Card>
                    <CardContent className="pt-6 text-center">
                      <div className="text-3xl font-bold text-green-600 mb-2">
                        {selectedBusiness.complianceScore}%
                      </div>
                      <p className="text-sm text-gray-600">Compliance Score</p>
                      <Progress value={selectedBusiness.complianceScore} className="mt-2" />
                    </CardContent>
                  </Card>

                  <Card>
                    <CardContent className="pt-6 text-center">
                      <div className="text-3xl font-bold text-blue-600 mb-2">
                        {selectedBusiness.totalInspections}
                      </div>
                      <p className="text-sm text-gray-600">Total Inspections</p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardContent className="pt-6 text-center">
                      <div className="text-3xl font-bold text-red-600 mb-2">
                        {selectedBusiness.violations}
                      </div>
                      <p className="text-sm text-gray-600">Total Violations</p>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardContent className="pt-6 text-center">
                      <div className="text-3xl font-bold text-yellow-600 mb-2">
                        ₱{selectedBusiness.outstandingFines.toLocaleString()}
                      </div>
                      <p className="text-sm text-gray-600">Outstanding Fines</p>
                    </CardContent>
                  </Card>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <Card>
                    <CardHeader>
                      <CardTitle>Business Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div>
                        <p className="text-sm text-gray-500">Registration Number</p>
                        <p className="font-medium">{selectedBusiness.registrationNumber}</p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-500">Business Type</p>
                        <p className="font-medium">{selectedBusiness.businessType}</p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-500">Phone</p>
                        <p className="font-medium flex items-center">
                          <Phone className="h-4 w-4 mr-2" />
                          {selectedBusiness.phone}
                        </p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-500">Email</p>
                        <p className="font-medium flex items-center">
                          <Mail className="h-4 w-4 mr-2" />
                          {selectedBusiness.email}
                        </p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-500">Permit Expiry</p>
                        <p className="font-medium flex items-center">
                          <Calendar className="h-4 w-4 mr-2" />
                          {new Date(selectedBusiness.permitExpiry).toLocaleDateString()}
                        </p>
                      </div>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader>
                      <CardTitle>Compliance Status</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="flex items-center justify-between">
                        <span>Overall Status</span>
                        <Badge className={getStatusColor(selectedBusiness.status)}>
                          {selectedBusiness.status}
                        </Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span>Risk Level</span>
                        <Badge className={getRiskColor(selectedBusiness.riskLevel)}>
                          {selectedBusiness.riskLevel}
                        </Badge>
                      </div>
                      <div className="flex items-center justify-between">
                        <span>Resolved Violations</span>
                        <span className="font-medium">
                          {selectedBusiness.resolvedViolations}/{selectedBusiness.violations}
                        </span>
                      </div>
                      <div className="flex items-center justify-between">
                        <span>Last Inspection</span>
                        <span className="font-medium">
                          {new Date(selectedBusiness.lastInspection).toLocaleDateString()}
                        </span>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>

              <TabsContent value="inspections" className="space-y-4">
                <div className="space-y-4">
                  {selectedBusiness.inspectionHistory.map((inspection) => (
                    <Card key={inspection.id}>
                      <CardContent className="pt-6">
                        <div className="flex items-center justify-between">
                          <div className="flex-1">
                            <div className="flex items-center space-x-3 mb-2">
                              <h4 className="font-medium">{inspection.type}</h4>
                              <Badge className={getInspectionStatusColor(inspection.status)}>
                                {inspection.status}
                              </Badge>
                            </div>
                            <p className="text-sm text-gray-600 mb-2">Inspector: {inspection.inspector}</p>
                            <p className="text-sm text-gray-500">{new Date(inspection.date).toLocaleDateString()}</p>
                          </div>
                          <div className="text-right">
                            <div className="text-2xl font-bold text-green-600 mb-1">
                              {inspection.score}%
                            </div>
                            <p className="text-sm text-gray-600">{inspection.violations} violations</p>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              </TabsContent>

              <TabsContent value="violations" className="space-y-4">
                <Card>
                  <CardHeader>
                    <CardTitle>Violation Summary</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div className="text-center">
                        <div className="text-3xl font-bold text-red-600">{selectedBusiness.violations}</div>
                        <p className="text-sm text-gray-600">Total Violations</p>
                      </div>
                      <div className="text-center">
                        <div className="text-3xl font-bold text-green-600">{selectedBusiness.resolvedViolations}</div>
                        <p className="text-sm text-gray-600">Resolved</p>
                      </div>
                      <div className="text-center">
                        <div className="text-3xl font-bold text-yellow-600">
                          {selectedBusiness.violations - selectedBusiness.resolvedViolations}
                        </div>
                        <p className="text-sm text-gray-600">Pending</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <TabsContent value="certifications" className="space-y-4">
                <Card>
                  <CardHeader>
                    <CardTitle>Business Certifications</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {selectedBusiness.certifications.map((cert, idx) => (
                        <div key={idx} className="flex items-center space-x-3 p-4 border rounded-lg">
                          <CheckCircle className="h-6 w-6 text-green-500" />
                          <div>
                            <p className="font-medium">{cert}</p>
                            <p className="text-sm text-gray-600">Valid</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold">Business Compliance Tracking</h2>
          <p className="text-gray-600 text-sm sm:text-base">Monitor compliance scores, inspection history, and violations</p>
        </div>
        <Button className="w-full sm:w-auto">
          <Building className="h-4 w-4 mr-2" />
          Register Business
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Businesses</p>
                <p className="text-2xl font-bold">{totalBusinesses}</p>
              </div>
              <Building className="h-12 w-12 text-blue-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Excellent Rating</p>
                <p className="text-2xl font-bold">{excellentBusinesses}</p>
              </div>
              <Star className="h-12 w-12 text-yellow-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Avg Compliance</p>
                <p className="text-2xl font-bold">{avgComplianceScore}%</p>
              </div>
              <TrendingUp className="h-12 w-12 text-green-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">High Risk</p>
                <p className="text-2xl font-bold">{highRiskBusinesses}</p>
              </div>
              <AlertTriangle className="h-12 w-12 text-red-500" />
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
                placeholder="Search by business name, type, or address..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full"
              />
            </div>
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md w-full sm:w-auto"
            >
              <option value="all">All Status</option>
              <option value="excellent">Excellent</option>
              <option value="good">Good</option>
              <option value="fair">Fair</option>
              <option value="poor">Poor</option>
              <option value="critical">Critical</option>
            </select>
          </div>
        </CardContent>
      </Card>

      {/* Business List */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {filteredBusinesses.map(renderBusinessCard)}
        {filteredBusinesses.length === 0 && (
          <Card className="lg:col-span-2">
            <CardContent className="text-center py-12">
              <Building className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No businesses found matching your search criteria.</p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Business Details Modal */}
      {selectedBusiness && renderBusinessDetails()}
    </div>
  );
}