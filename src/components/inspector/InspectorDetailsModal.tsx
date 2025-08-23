import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Progress } from '../ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import { Avatar, AvatarFallback, AvatarImage } from '../ui/avatar';
import { Calendar, MapPin, Phone, Mail, CheckCircle } from 'lucide-react';
import { Inspector } from '../constants/inspectorData';
import { getStatusColor, getCertificationColor, getInspectionStatusColor } from '../utils/inspectorUtils';

interface InspectorDetailsModalProps {
  inspector: Inspector | null;
  onClose: () => void;
}

export function InspectorDetailsModal({ inspector, onClose }: InspectorDetailsModalProps) {
  if (!inspector) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <Card className="w-full max-w-6xl max-h-[90vh] overflow-y-auto">
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <Avatar className="h-16 w-16">
                <AvatarImage src={inspector.email} />
                <AvatarFallback>{inspector.name.split(' ').map(n => n[0]).join('')}</AvatarFallback>
              </Avatar>
              <div>
                <CardTitle className="text-2xl">{inspector.name}</CardTitle>
                <CardDescription>{inspector.department}</CardDescription>
              </div>
            </div>
            <Button variant="outline" onClick={onClose}>
              Close
            </Button>
          </div>
        </CardHeader>
        <CardContent className="space-y-6">
          <Tabs defaultValue="overview" className="w-full">
            <TabsList className="grid w-full grid-cols-4">
              <TabsTrigger value="overview">Overview</TabsTrigger>
              <TabsTrigger value="performance">Performance</TabsTrigger>
              <TabsTrigger value="certifications">Certifications</TabsTrigger>
              <TabsTrigger value="inspections">Recent Inspections</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                  <CardContent className="pt-6 text-center">
                    <div className="text-3xl font-bold text-green-600 mb-2">
                      {inspector.performanceScore}%
                    </div>
                    <p className="text-sm text-gray-600">Performance Score</p>
                    <Progress value={inspector.performanceScore} className="mt-2" />
                  </CardContent>
                </Card>

                <Card>
                  <CardContent className="pt-6 text-center">
                    <div className="text-3xl font-bold text-blue-600 mb-2">
                      {inspector.totalInspections}
                    </div>
                    <p className="text-sm text-gray-600">Total Inspections</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardContent className="pt-6 text-center">
                    <div className="text-3xl font-bold text-purple-600 mb-2">
                      {inspector.accuracyRate}%
                    </div>
                    <p className="text-sm text-gray-600">Accuracy Rate</p>
                  </CardContent>
                </Card>

                <Card>
                  <CardContent className="pt-6 text-center">
                    <div className="text-3xl font-bold text-yellow-600 mb-2">
                      {inspector.workload}
                    </div>
                    <p className="text-sm text-gray-600">Active Workload</p>
                  </CardContent>
                </Card>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <Card>
                  <CardHeader>
                    <CardTitle>Personal Information</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div>
                      <p className="text-sm text-gray-500">Inspector ID</p>
                      <p className="font-medium">{inspector.id}</p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Email</p>
                      <p className="font-medium flex items-center">
                        <Mail className="h-4 w-4 mr-2" />
                        {inspector.email}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Phone</p>
                      <p className="font-medium flex items-center">
                        <Phone className="h-4 w-4 mr-2" />
                        {inspector.phone}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Location</p>
                      <p className="font-medium flex items-center">
                        <MapPin className="h-4 w-4 mr-2" />
                        {inspector.location}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Join Date</p>
                      <p className="font-medium flex items-center">
                        <Calendar className="h-4 w-4 mr-2" />
                        {new Date(inspector.joinDate).toLocaleDateString()}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Status</p>
                      <Badge className={getStatusColor(inspector.status)}>
                        {inspector.status.replace('_', ' ')}
                      </Badge>
                    </div>
                  </CardContent>
                </Card>

                <Card>
                  <CardHeader>
                    <CardTitle>Specializations</CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-3">
                      {inspector.specialization.map((spec, idx) => (
                        <div key={idx} className="flex items-center space-x-3 p-3 border rounded-lg">
                          <CheckCircle className="h-5 w-5 text-green-500" />
                          <span className="font-medium">{spec}</span>
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

            <TabsContent value="performance" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Performance Metrics</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                      <p className="text-sm text-gray-500 mb-2">Overall Performance</p>
                      <div className="text-2xl font-bold text-green-600 mb-2">{inspector.performanceScore}%</div>
                      <Progress value={inspector.performanceScore} />
                    </div>
                    <div>
                      <p className="text-sm text-gray-500 mb-2">Accuracy Rate</p>
                      <div className="text-2xl font-bold text-blue-600 mb-2">{inspector.accuracyRate}%</div>
                      <Progress value={inspector.accuracyRate} />
                    </div>
                    <div>
                      <p className="text-sm text-gray-500 mb-2">Current Workload</p>
                      <div className="text-2xl font-bold text-purple-600 mb-2">{inspector.workload}</div>
                      <p className="text-sm text-gray-600">Active inspections</p>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="certifications" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Certifications & Licenses</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {inspector.certifications.map((cert) => (
                      <div key={cert.id} className="border rounded-lg p-4">
                        <div className="flex items-center justify-between mb-3">
                          <h4 className="font-medium">{cert.name}</h4>
                          <Badge className={getCertificationColor(cert.status)}>
                            {cert.status}
                          </Badge>
                        </div>
                        <p className="text-sm text-gray-600 mb-2">Issued by: {cert.issuer}</p>
                        <div className="grid grid-cols-2 gap-2 text-sm">
                          <div>
                            <p className="text-gray-500">Issue Date</p>
                            <p>{new Date(cert.issueDate).toLocaleDateString()}</p>
                          </div>
                          <div>
                            <p className="text-gray-500">Expiry Date</p>
                            <p>{new Date(cert.expiryDate).toLocaleDateString()}</p>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="inspections" className="space-y-4">
              <div className="space-y-4">
                {inspector.recentInspections.map((inspection) => (
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
                          <p className="text-sm text-gray-600 mb-2">Business: {inspection.businessName}</p>
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
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
}