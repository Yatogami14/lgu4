import React, { useState } from 'react';
import { AlertTriangle, FileText, DollarSign, Calendar, MapPin, User, Search, Filter, Plus, Eye, Edit, Trash2, CreditCard, Clock } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Badge } from './ui/badge';
import { Textarea } from './ui/textarea';
import { Label } from './ui/label';
import { Alert, AlertDescription } from './ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { Avatar, AvatarFallback } from './ui/avatar';

interface Violation {
  id: string;
  ticketId: string;
  businessName: string;
  businessAddress: string;
  violationType: string;
  severity: 'minor' | 'major' | 'critical';
  description: string;
  lawReference: string;
  penaltyAmount: number;
  dateIssued: string;
  dueDate: string;
  status: 'issued' | 'appealed' | 'resolved' | 'unpaid' | 'overdue';
  inspectorName: string;
  inspectorId: string;
  evidence?: string[];
  paymentHistory?: PaymentRecord[];
}

interface PaymentRecord {
  id: string;
  amount: number;
  date: string;
  method: string;
  referenceNumber: string;
  status: 'completed' | 'pending' | 'failed';
}

const mockViolations: Violation[] = [
  {
    id: '1',
    ticketId: 'VIO-2024-001',
    businessName: 'ABC Restaurant',
    businessAddress: '123 Main St, Makati City',
    violationType: 'Food Safety',
    severity: 'major',
    description: 'Improper food storage temperatures observed. Refrigeration units not maintaining required temperature below 40°F.',
    lawReference: 'Food Safety Act 2013, Section 4.2.1',
    penaltyAmount: 15000,
    dateIssued: '2024-01-10',
    dueDate: '2024-01-25',
    status: 'issued',
    inspectorName: 'Juan Dela Cruz',
    inspectorId: '101',
    evidence: ['photo1.jpg', 'temperature_log.pdf']
  },
  {
    id: '2',
    ticketId: 'VIO-2024-002',
    businessName: 'XYZ Mall',
    businessAddress: '456 Commerce Ave, BGC',
    violationType: 'Fire Safety',
    severity: 'critical',
    description: 'Fire exits blocked by merchandise. Emergency lighting system non-functional.',
    lawReference: 'Fire Code of the Philippines, Section 703',
    penaltyAmount: 50000,
    dateIssued: '2024-01-12',
    dueDate: '2024-01-27',
    status: 'appealed',
    inspectorName: 'Anna Reyes',
    inspectorId: '102',
    evidence: ['fire_exit_photo.jpg', 'emergency_light_video.mp4']
  },
  {
    id: '3',
    ticketId: 'VIO-2024-003',
    businessName: 'Tech Hub Office',
    businessAddress: '789 IT Park, Cebu',
    violationType: 'Building Safety',
    severity: 'minor',
    description: 'Missing occupancy permit display. No visible building permit posted.',
    lawReference: 'National Building Code, Section 301',
    penaltyAmount: 5000,
    dateIssued: '2024-01-08',
    dueDate: '2024-01-23',
    status: 'resolved',
    inspectorName: 'Carlos Garcia',
    inspectorId: '103',
    paymentHistory: [
      {
        id: 'pay-1',
        amount: 5000,
        date: '2024-01-20',
        method: 'Bank Transfer',
        referenceNumber: 'BT-2024-001',
        status: 'completed'
      }
    ]
  }
];

export function ViolationManager() {
  const [violations, setViolations] = useState<Violation[]>(mockViolations);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [severityFilter, setSeverityFilter] = useState('all');
  const [showNewViolation, setShowNewViolation] = useState(false);
  const [selectedViolation, setSelectedViolation] = useState<Violation | null>(null);
  const [showPaymentForm, setShowPaymentForm] = useState(false);

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'minor': return 'bg-yellow-100 text-yellow-800';
      case 'major': return 'bg-orange-100 text-orange-800';
      case 'critical': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'issued': return 'bg-blue-100 text-blue-800';
      case 'appealed': return 'bg-purple-100 text-purple-800';
      case 'resolved': return 'bg-green-100 text-green-800';
      case 'unpaid': return 'bg-red-100 text-red-800';
      case 'overdue': return 'bg-red-200 text-red-900';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const filteredViolations = violations.filter(violation => {
    const matchesSearch = violation.businessName.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         violation.ticketId.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         violation.violationType.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || violation.status === statusFilter;
    const matchesSeverity = severityFilter === 'all' || violation.severity === severityFilter;
    
    return matchesSearch && matchesStatus && matchesSeverity;
  });

  const totalViolations = violations.length;
  const pendingPayments = violations.filter(v => v.status === 'issued' || v.status === 'unpaid').length;
  const totalPenalties = violations.reduce((sum, v) => sum + v.penaltyAmount, 0);
  const collectedAmount = violations
    .filter(v => v.status === 'resolved')
    .reduce((sum, v) => sum + v.penaltyAmount, 0);

  const renderViolationCard = (violation: Violation) => (
    <Card key={violation.id} className="hover:shadow-md transition-shadow">
      <CardContent className="pt-6">
        <div className="flex flex-col lg:flex-row lg:items-start justify-between space-y-4 lg:space-y-0 mb-4">
          <div className="flex-1 min-w-0">
            <div className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 mb-2">
              <h3 className="font-semibold text-lg truncate">{violation.businessName}</h3>
              <div className="flex space-x-2">
                <Badge className={`${getSeverityColor(violation.severity)} text-xs`}>
                  {violation.severity}
                </Badge>
                <Badge className={`${getStatusColor(violation.status)} text-xs`}>
                  {violation.status}
                </Badge>
              </div>
            </div>
            <p className="text-gray-600 mb-2 text-sm break-words">{violation.businessAddress}</p>
            <p className="text-sm text-gray-800 mb-3 break-words">{violation.description}</p>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              <div>
                <p className="text-gray-500">Ticket ID</p>
                <p className="font-medium">{violation.ticketId}</p>
              </div>
              <div>
                <p className="text-gray-500">Violation Type</p>
                <p className="font-medium">{violation.violationType}</p>
              </div>
              <div>
                <p className="text-gray-500">Inspector</p>
                <p className="font-medium">{violation.inspectorName}</p>
              </div>
              <div>
                <p className="text-gray-500">Due Date</p>
                <p className="font-medium">{new Date(violation.dueDate).toLocaleDateString()}</p>
              </div>
            </div>
          </div>
          
          <div className="flex flex-row sm:flex-col lg:flex-col justify-between sm:justify-end lg:text-right items-start lg:ml-4 space-x-4 sm:space-x-0 lg:space-x-0 sm:space-y-3 lg:space-y-0 flex-shrink-0">
            <div className="text-left sm:text-right lg:text-right">
              <p className="text-xs sm:text-sm text-gray-500">Penalty Amount</p>
              <p className="text-lg sm:text-xl lg:text-2xl font-bold text-red-600">₱{violation.penaltyAmount.toLocaleString()}</p>
            </div>
            <div className="flex flex-row sm:flex-row space-x-2 sm:mt-3 lg:mt-3">
              <Button size="sm" variant="outline" onClick={() => setSelectedViolation(violation)}>
                <Eye className="h-4 w-4" />
              </Button>
              <Button size="sm" variant="outline">
                <Edit className="h-4 w-4" />
              </Button>
              {violation.status === 'issued' && (
                <Button size="sm" onClick={() => {
                  setSelectedViolation(violation);
                  setShowPaymentForm(true);
                }}>
                  <CreditCard className="h-4 w-4" />
                </Button>
              )}
            </div>
          </div>
        </div>
        
        <div className="border-t pt-3 mt-3">
          <p className="text-xs text-gray-500 mb-1">Law Reference</p>
          <p className="text-sm break-words">{violation.lawReference}</p>
        </div>
      </CardContent>
    </Card>
  );

  const renderNewViolationForm = () => (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <Card className="w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <CardHeader>
          <CardTitle className="text-lg sm:text-xl">Issue New Violation Citation</CardTitle>
          <CardDescription className="text-sm">AI-assisted violation detection and citation generation</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4 sm:space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="businessName">Business Name</Label>
              <Input id="businessName" placeholder="Enter business name" />
            </div>
            <div>
              <Label htmlFor="violationType">Violation Type</Label>
              <Select>
                <SelectTrigger>
                  <SelectValue placeholder="Select violation type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="health">Health & Sanitation</SelectItem>
                  <SelectItem value="fire">Fire Safety</SelectItem>
                  <SelectItem value="building">Building Safety</SelectItem>
                  <SelectItem value="environmental">Environmental</SelectItem>
                  <SelectItem value="food">Food Safety</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div>
            <Label htmlFor="businessAddress">Business Address</Label>
            <Input id="businessAddress" placeholder="Complete business address" />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="severity">Severity Level</Label>
              <Select>
                <SelectTrigger>
                  <SelectValue placeholder="Select severity" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="minor">Minor</SelectItem>
                  <SelectItem value="major">Major</SelectItem>
                  <SelectItem value="critical">Critical</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label htmlFor="penaltyAmount">Penalty Amount (₱)</Label>
              <Input id="penaltyAmount" type="number" placeholder="0" />
            </div>
          </div>

          <div>
            <Label htmlFor="description">Violation Description</Label>
            <Textarea
              id="description"
              placeholder="Detailed description of the violation observed..."
              rows={4}
            />
          </div>

          <div>
            <Label htmlFor="lawReference">Law Reference</Label>
            <Input id="lawReference" placeholder="Applicable law or regulation reference" />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="dateIssued">Date Issued</Label>
              <Input id="dateIssued" type="date" defaultValue={new Date().toISOString().split('T')[0]} />
            </div>
            <div>
              <Label htmlFor="dueDate">Payment Due Date</Label>
              <Input id="dueDate" type="date" />
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-4">
            <Button variant="outline" onClick={() => setShowNewViolation(false)}>
              Cancel
            </Button>
            <Button onClick={() => setShowNewViolation(false)}>
              Issue Citation
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );

  const renderViolationDetails = () => {
    if (!selectedViolation) return null;

    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <Card className="w-full max-w-4xl max-h-[90vh] overflow-y-auto">
          <CardHeader>
            <div className="flex flex-col sm:flex-row sm:items-center justify-between space-y-2 sm:space-y-0">
              <div className="min-w-0 flex-1">
                <CardTitle className="text-lg sm:text-xl truncate">Violation Details - {selectedViolation.ticketId}</CardTitle>
                <CardDescription className="text-sm truncate">{selectedViolation.businessName}</CardDescription>
              </div>
              <Button variant="outline" onClick={() => setSelectedViolation(null)} className="w-full sm:w-auto">
                Close
              </Button>
            </div>
          </CardHeader>
          <CardContent className="space-y-4 sm:space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Business Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div>
                    <p className="text-sm text-gray-500">Business Name</p>
                    <p className="font-medium">{selectedViolation.businessName}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Address</p>
                    <p className="font-medium">{selectedViolation.businessAddress}</p>
                  </div>
                  <div className="flex space-x-4">
                    <div>
                      <p className="text-sm text-gray-500">Severity</p>
                      <Badge className={getSeverityColor(selectedViolation.severity)}>
                        {selectedViolation.severity}
                      </Badge>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Status</p>
                      <Badge className={getStatusColor(selectedViolation.status)}>
                        {selectedViolation.status}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Citation Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div>
                    <p className="text-sm text-gray-500">Violation Type</p>
                    <p className="font-medium">{selectedViolation.violationType}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Date Issued</p>
                    <p className="font-medium">{new Date(selectedViolation.dateIssued).toLocaleDateString()}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Payment Due</p>
                    <p className="font-medium">{new Date(selectedViolation.dueDate).toLocaleDateString()}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-500">Penalty Amount</p>
                    <p className="text-xl font-bold text-red-600">₱{selectedViolation.penaltyAmount.toLocaleString()}</p>
                  </div>
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Violation Description</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-gray-800">{selectedViolation.description}</p>
                <div className="mt-4">
                  <p className="text-sm text-gray-500 mb-1">Legal Reference</p>
                  <p className="font-medium">{selectedViolation.lawReference}</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Inspector Information</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center space-x-3">
                  <Avatar>
                    <AvatarFallback>{selectedViolation.inspectorName.split(' ').map(n => n[0]).join('')}</AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="font-medium">{selectedViolation.inspectorName}</p>
                    <p className="text-sm text-gray-600">Inspector ID: {selectedViolation.inspectorId}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {selectedViolation.evidence && selectedViolation.evidence.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Evidence Files</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {selectedViolation.evidence.map((file, idx) => (
                      <div key={idx} className="border rounded-lg p-3 text-center">
                        <FileText className="h-8 w-8 mx-auto mb-2 text-gray-400" />
                        <p className="text-sm truncate">{file}</p>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            )}

            {selectedViolation.paymentHistory && selectedViolation.paymentHistory.length > 0 && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">Payment History</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {selectedViolation.paymentHistory.map((payment) => (
                      <div key={payment.id} className="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                          <p className="font-medium">₱{payment.amount.toLocaleString()}</p>
                          <p className="text-sm text-gray-600">{payment.method}</p>
                          <p className="text-xs text-gray-500">Ref: {payment.referenceNumber}</p>
                        </div>
                        <div className="text-right">
                          <Badge className={payment.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}>
                            {payment.status}
                          </Badge>
                          <p className="text-xs text-gray-500 mt-1">{new Date(payment.date).toLocaleDateString()}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            )}
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
          <h2 className="text-xl sm:text-2xl font-bold">Violation Management</h2>
          <p className="text-gray-600 text-sm sm:text-base">Track violations, manage citations, and process payments</p>
        </div>
        <Button onClick={() => setShowNewViolation(true)} className="w-full sm:w-auto">
          <Plus className="h-4 w-4 mr-2" />
          Issue Citation
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Violations</p>
                <p className="text-2xl font-bold">{totalViolations}</p>
              </div>
              <AlertTriangle className="h-12 w-12 text-red-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Pending Payments</p>
                <p className="text-2xl font-bold">{pendingPayments}</p>
              </div>
              <Clock className="h-12 w-12 text-yellow-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Penalties</p>
                <p className="text-2xl font-bold">₱{totalPenalties.toLocaleString()}</p>
              </div>
              <DollarSign className="h-12 w-12 text-blue-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Collected Amount</p>
                <p className="text-2xl font-bold">₱{collectedAmount.toLocaleString()}</p>
              </div>
              <CreditCard className="h-12 w-12 text-green-500" />
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
                placeholder="Search by business name, ticket ID, or violation type..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full"
              />
            </div>
            <div className="flex flex-col sm:flex-row gap-4">
              <Select value={statusFilter} onValueChange={setStatusFilter}>
                <SelectTrigger className="w-full sm:w-40">
                  <SelectValue placeholder="All Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Status</SelectItem>
                  <SelectItem value="issued">Issued</SelectItem>
                  <SelectItem value="appealed">Appealed</SelectItem>
                  <SelectItem value="resolved">Resolved</SelectItem>
                  <SelectItem value="unpaid">Unpaid</SelectItem>
                  <SelectItem value="overdue">Overdue</SelectItem>
                </SelectContent>
              </Select>
              <Select value={severityFilter} onValueChange={setSeverityFilter}>
                <SelectTrigger className="w-full sm:w-40">
                  <SelectValue placeholder="All Severity" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Severity</SelectItem>
                  <SelectItem value="minor">Minor</SelectItem>
                  <SelectItem value="major">Major</SelectItem>
                  <SelectItem value="critical">Critical</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Violations List */}
      <div className="space-y-4">
        {filteredViolations.map(renderViolationCard)}
        {filteredViolations.length === 0 && (
          <Card>
            <CardContent className="text-center py-12">
              <AlertTriangle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">No violations found matching your filters.</p>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Modals */}
      {showNewViolation && renderNewViolationForm()}
      {selectedViolation && !showPaymentForm && renderViolationDetails()}
    </div>
  );
}