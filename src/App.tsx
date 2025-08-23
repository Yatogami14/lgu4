import React, { useState, useEffect } from 'react';
import { Calendar, Users, FileText, AlertTriangle, BarChart3, Settings, Camera, MapPin, Clock, CheckCircle, XCircle, User, LogOut, Shield, Bell } from 'lucide-react';
import { Button } from './components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './components/ui/card';
import { Badge } from './components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './components/ui/tabs';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './components/ui/select';
import { Input } from './components/ui/input';
import { Textarea } from './components/ui/textarea';
import { Avatar, AvatarFallback, AvatarImage } from './components/ui/avatar';
import { Alert, AlertDescription } from './components/ui/alert';
import { Progress } from './components/ui/progress';
import { InspectionScheduler } from './components/InspectionScheduler';
import { InspectionForm } from './components/InspectionForm';
import { ViolationManager } from './components/ViolationManager';
import { BusinessCompliance } from './components/BusinessCompliance';
import { InspectorDashboard } from './components/InspectorDashboard';
import { AnalyticsDashboard } from './components/AnalyticsDashboard';

type UserRole = 'super_admin' | 'admin' | 'inspector' | 'business_owner' | 'community_user';

interface User {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  avatar?: string;
  department?: string;
  certification?: string;
}

interface Inspection {
  id: string;
  type: string;
  businessName: string;
  businessAddress: string;
  scheduledDate: string;
  inspectorId: string;
  inspectorName: string;
  status: 'scheduled' | 'in_progress' | 'completed' | 'overdue';
  complianceScore?: number;
  violations?: number;
  priority: 'low' | 'medium' | 'high';
}

const mockUser: User = {
  id: '1',
  name: 'Maria Santos',
  email: 'maria.santos@lgu.gov.ph',
  role: 'admin',
  department: 'Health and Safety Division',
  certification: 'Certified Safety Inspector'
};

const mockInspections: Inspection[] = [
  {
    id: '1',
    type: 'Health & Sanitation',
    businessName: 'ABC Restaurant',
    businessAddress: '123 Main St, Makati City',
    scheduledDate: '2024-01-15',
    inspectorId: '101',
    inspectorName: 'Juan Dela Cruz',
    status: 'scheduled',
    priority: 'high'
  },
  {
    id: '2',
    type: 'Fire Safety',
    businessName: 'XYZ Mall',
    businessAddress: '456 Commerce Ave, BGC',
    scheduledDate: '2024-01-16',
    inspectorId: '102',
    inspectorName: 'Anna Reyes',
    status: 'in_progress',
    complianceScore: 85,
    violations: 2,
    priority: 'medium'
  },
  {
    id: '3',
    type: 'Building Safety',
    businessName: 'Tech Hub Office',
    businessAddress: '789 IT Park, Cebu',
    scheduledDate: '2024-01-14',
    inspectorId: '103',
    inspectorName: 'Carlos Garcia',
    status: 'completed',
    complianceScore: 92,
    violations: 1,
    priority: 'low'
  }
];

function App() {
  const [currentUser, setCurrentUser] = useState<User>(mockUser);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [inspections, setInspections] = useState<Inspection[]>(mockInspections);
  const [notifications, setNotifications] = useState([
    { id: '1', message: 'New inspection scheduled for ABC Restaurant', time: '2 hours ago', type: 'info' },
    { id: '2', message: 'Violation reported at XYZ Mall - Fire Exit Blocked', time: '4 hours ago', type: 'warning' },
    { id: '3', message: 'Inspector certification expires in 30 days', time: '1 day ago', type: 'alert' }
  ]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'scheduled': return 'bg-blue-100 text-blue-800';
      case 'in_progress': return 'bg-yellow-100 text-yellow-800';
      case 'completed': return 'bg-green-100 text-green-800';
      case 'overdue': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'high': return 'bg-red-500';
      case 'medium': return 'bg-yellow-500';
      case 'low': return 'bg-green-500';
      default: return 'bg-gray-500';
    }
  };

  const renderDashboard = () => (
    <div className="space-y-6">
      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Total Inspections</p>
                <p className="text-2xl font-bold">247</p>
                <p className="text-xs text-green-600">+12% from last month</p>
              </div>
              <FileText className="h-12 w-12 text-blue-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Active Violations</p>
                <p className="text-2xl font-bold">23</p>
                <p className="text-xs text-red-600">+3 new today</p>
              </div>
              <AlertTriangle className="h-12 w-12 text-red-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Compliance Rate</p>
                <p className="text-2xl font-bold">87%</p>
                <p className="text-xs text-green-600">+2% improvement</p>
              </div>
              <CheckCircle className="h-12 w-12 text-green-500" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-600">Active Inspectors</p>
                <p className="text-2xl font-bold">15</p>
                <p className="text-xs text-blue-600">All certified</p>
              </div>
              <Users className="h-12 w-12 text-purple-500" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Inspections */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Inspections</CardTitle>
          <CardDescription>Latest inspection activities and status updates</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {inspections.map((inspection) => (
              <div key={inspection.id} className="flex flex-col sm:flex-row sm:items-center justify-between p-4 border rounded-lg space-y-3 sm:space-y-0">
                <div className="flex items-start space-x-3 flex-1 min-w-0">
                  <div className={`w-3 h-3 rounded-full mt-1 flex-shrink-0 ${getPriorityColor(inspection.priority)}`} />
                  <div className="min-w-0 flex-1">
                    <p className="font-medium truncate">{inspection.businessName}</p>
                    <p className="text-sm text-gray-600 break-words">{inspection.type}</p>
                    <p className="text-sm text-gray-600 break-words">{inspection.businessAddress}</p>
                    <p className="text-xs text-gray-500">Inspector: {inspection.inspectorName}</p>
                  </div>
                </div>
                <div className="flex flex-row sm:flex-col lg:flex-row items-start sm:items-end lg:items-center space-x-3 sm:space-x-0 lg:space-x-4 sm:space-y-2 lg:space-y-0 flex-shrink-0">
                  {inspection.complianceScore && (
                    <div className="text-left sm:text-right">
                      <p className="text-xs sm:text-sm">Compliance</p>
                      <p className="font-bold text-green-600 text-sm sm:text-base">{inspection.complianceScore}%</p>
                    </div>
                  )}
                  <div className="flex flex-col space-y-1">
                    <Badge className={`${getStatusColor(inspection.status)} text-xs`}>
                      {inspection.status.replace('_', ' ')}
                    </Badge>
                    <p className="text-xs sm:text-sm text-gray-500">{inspection.scheduledDate}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Notifications */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Notifications</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {notifications.map((notification) => (
              <Alert key={notification.id}>
                <Bell className="h-4 w-4" />
                <AlertDescription>
                  <div className="flex flex-col sm:flex-row sm:justify-between sm:items-start space-y-1 sm:space-y-0">
                    <span className="text-sm break-words pr-2">{notification.message}</span>
                    <span className="text-xs text-gray-500 flex-shrink-0">{notification.time}</span>
                  </div>
                </AlertDescription>
              </Alert>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );

  const renderUserProfile = () => (
    <Card>
      <CardHeader>
        <CardTitle>User Profile</CardTitle>
      </CardHeader>
      <CardContent className="space-y-6">
        <div className="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-4">
          <Avatar className="h-16 w-16 sm:h-20 sm:w-20">
            <AvatarImage src={currentUser.avatar} />
            <AvatarFallback>{currentUser.name.split(' ').map(n => n[0]).join('')}</AvatarFallback>
          </Avatar>
          <div className="text-center sm:text-left">
            <h3 className="text-lg sm:text-xl font-bold">{currentUser.name}</h3>
            <p className="text-gray-600 text-sm sm:text-base break-all">{currentUser.email}</p>
            <Badge className="mt-2">{currentUser.role.replace('_', ' ')}</Badge>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="text-sm font-medium">Department</label>
            <Input value={currentUser.department || ''} readOnly />
          </div>
          <div>
            <label className="text-sm font-medium">Certification</label>
            <Input value={currentUser.certification || ''} readOnly />
          </div>
        </div>

        <div>
          <label className="text-sm font-medium">Role Permissions</label>
          <div className="mt-2 space-y-2">
            <div className="flex items-center space-x-2">
              <CheckCircle className="h-4 w-4 text-green-500" />
              <span className="text-sm">Manage Inspections</span>
            </div>
            <div className="flex items-center space-x-2">
              <CheckCircle className="h-4 w-4 text-green-500" />
              <span className="text-sm">Issue Violations</span>
            </div>
            <div className="flex items-center space-x-2">
              <CheckCircle className="h-4 w-4 text-green-500" />
              <span className="text-sm">View Analytics</span>
            </div>
            <div className="flex items-center space-x-2">
              <CheckCircle className="h-4 w-4 text-green-500" />
              <span className="text-sm">Assign Inspectors</span>
            </div>
          </div>
        </div>

        <Button className="w-full">Update Profile</Button>
      </CardContent>
    </Card>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1">
              <Shield className="h-6 w-6 sm:h-8 sm:w-8 text-blue-600 flex-shrink-0" />
              <div className="min-w-0 flex-1">
                <h1 className="text-sm sm:text-xl font-bold text-gray-900 truncate">LGU Health & Safety</h1>
                <p className="text-xs sm:text-sm text-gray-600 hidden sm:block">Digital Inspection Platform</p>
              </div>
            </div>
            
            <div className="flex items-center space-x-1 sm:space-x-4 flex-shrink-0">
              <Button variant="outline" size="sm" className="hidden sm:flex">
                <Bell className="h-4 w-4 mr-2" />
                <span className="hidden md:inline">Notifications</span>
                <Badge variant="destructive" className="ml-2">3</Badge>
              </Button>
              
              <Button variant="outline" size="sm" className="sm:hidden">
                <Bell className="h-4 w-4" />
                <Badge variant="destructive" className="ml-1 text-xs px-1">3</Badge>
              </Button>
              
              <div className="flex items-center space-x-2">
                <Avatar className="h-8 w-8">
                  <AvatarImage src={currentUser.avatar} />
                  <AvatarFallback className="text-xs">{currentUser.name.split(' ').map(n => n[0]).join('')}</AvatarFallback>
                </Avatar>
                <div className="hidden lg:block">
                  <p className="text-sm font-medium">{currentUser.name}</p>
                  <p className="text-xs text-gray-600">{currentUser.role.replace('_', ' ')}</p>
                </div>
              </div>
              
              <Button variant="outline" size="sm">
                <LogOut className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
          <div className="w-full overflow-x-auto">
            <TabsList className="grid w-full grid-cols-4 sm:grid-cols-4 lg:grid-cols-8 min-w-max">
              <TabsTrigger value="dashboard" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <BarChart3 className="h-4 w-4 flex-shrink-0" />
                <span className="hidden sm:inline text-xs sm:text-sm truncate">Dashboard</span>
              </TabsTrigger>
              <TabsTrigger value="inspections" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <FileText className="h-4 w-4 flex-shrink-0" />
                <span className="hidden sm:inline text-xs sm:text-sm truncate">Inspections</span>
              </TabsTrigger>
              <TabsTrigger value="schedule" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <Calendar className="h-4 w-4 flex-shrink-0" />
                <span className="hidden sm:inline text-xs sm:text-sm truncate">Schedule</span>
              </TabsTrigger>
              <TabsTrigger value="violations" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <AlertTriangle className="h-4 w-4 flex-shrink-0" />
                <span className="hidden sm:inline text-xs sm:text-sm truncate">Violations</span>
              </TabsTrigger>
              <TabsTrigger value="businesses" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <Users className="h-4 w-4 flex-shrink-0" />
                <span className="hidden lg:inline text-xs sm:text-sm truncate">Businesses</span>
              </TabsTrigger>
              <TabsTrigger value="inspectors" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <User className="h-4 w-4 flex-shrink-0" />
                <span className="hidden lg:inline text-xs sm:text-sm truncate">Inspectors</span>
              </TabsTrigger>
              <TabsTrigger value="analytics" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <BarChart3 className="h-4 w-4 flex-shrink-0" />
                <span className="hidden lg:inline text-xs sm:text-sm truncate">Analytics</span>
              </TabsTrigger>
              <TabsTrigger value="profile" className="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3">
                <Settings className="h-4 w-4 flex-shrink-0" />
                <span className="hidden lg:inline text-xs sm:text-sm truncate">Profile</span>
              </TabsTrigger>
            </TabsList>
          </div>

          <TabsContent value="dashboard" className="space-y-6">
            {renderDashboard()}
          </TabsContent>

          <TabsContent value="inspections" className="space-y-6">
            <InspectionForm />
          </TabsContent>

          <TabsContent value="schedule" className="space-y-6">
            <InspectionScheduler inspections={inspections} />
          </TabsContent>

          <TabsContent value="violations" className="space-y-6">
            <ViolationManager />
          </TabsContent>

          <TabsContent value="businesses" className="space-y-6">
            <BusinessCompliance />
          </TabsContent>

          <TabsContent value="inspectors" className="space-y-6">
            <InspectorDashboard />
          </TabsContent>

          <TabsContent value="analytics" className="space-y-6">
            <AnalyticsDashboard />
          </TabsContent>

          <TabsContent value="profile" className="space-y-6">
            {renderUserProfile()}
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}

export default App;