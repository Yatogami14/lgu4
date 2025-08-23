import React, { useState } from "react";
import {
  Camera,
  Video,
  MapPin,
  Clock,
  CheckCircle,
  AlertTriangle,
  FileText,
  Upload,
  Zap,
  Save,
  Send,
} from "lucide-react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "./ui/card";
import { Button } from "./ui/button";
import { Input } from "./ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "./ui/select";
import { Badge } from "./ui/badge";
import { Textarea } from "./ui/textarea";
import { Label } from "./ui/label";
import { Checkbox } from "./ui/checkbox";
import { Progress } from "./ui/progress";
import { Alert, AlertDescription } from "./ui/alert";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "./ui/tabs";

interface ChecklistItem {
  id: string;
  category: string;
  question: string;
  required: boolean;
  type: "checkbox" | "text" | "select" | "number";
  options?: string[];
  response?: any;
  aiAnalysis?: {
    compliance: "compliant" | "non_compliant" | "needs_review";
    confidence: number;
    suggestions?: string[];
  };
}

interface MediaFile {
  id: string;
  name: string;
  type: "image" | "video";
  url: string;
  timestamp: string;
  location?: string;
  aiAnalysis?: {
    hazards: string[];
    confidence: number;
    compliance: "pass" | "fail" | "warning";
  };
}

export function InspectionForm() {
  const [selectedInspectionType, setSelectedInspectionType] =
    useState("health");
  const [checklist, setChecklist] = useState<ChecklistItem[]>(
    [],
  );
  const [mediaFiles, setMediaFiles] = useState<MediaFile[]>([]);
  const [inspectionNotes, setInspectionNotes] = useState("");
  const [complianceScore, setComplianceScore] = useState(0);
  const [activeTab, setActiveTab] = useState("checklist");
  const [aiProcessing, setAiProcessing] = useState(false);

  const inspectionTypes = {
    health: "Health & Sanitation",
    fire: "Fire Safety",
    building: "Building Safety",
    environmental: "Environmental",
    food: "Food Safety",
  };

  const healthChecklistItems: ChecklistItem[] = [
    {
      id: "1",
      category: "General Cleanliness",
      question:
        "Are the premises generally clean and well-maintained?",
      required: true,
      type: "checkbox",
    },
    {
      id: "2",
      category: "Waste Management",
      question:
        "Describe the waste disposal system and its condition",
      required: true,
      type: "text",
    },
    {
      id: "3",
      category: "Water Supply",
      question: "Rate the water supply quality",
      required: true,
      type: "select",
      options: [
        "Excellent",
        "Good",
        "Fair",
        "Poor",
        "Not Available",
      ],
    },
    {
      id: "4",
      category: "Pest Control",
      question: "Evidence of pest control measures?",
      required: true,
      type: "checkbox",
    },
    {
      id: "5",
      category: "Food Storage",
      question: "Number of food storage violations observed",
      required: false,
      type: "number",
    },
    {
      id: "6",
      category: "Employee Hygiene",
      question:
        "Describe employee hygiene practices and compliance",
      required: true,
      type: "text",
    },
  ];

  const fireChecklistItems: ChecklistItem[] = [
    {
      id: "1",
      category: "Fire Exits",
      question:
        "Are all fire exits clearly marked and unobstructed?",
      required: true,
      type: "checkbox",
    },
    {
      id: "2",
      category: "Fire Extinguishers",
      question:
        "Number of functional fire extinguishers present",
      required: true,
      type: "number",
    },
    {
      id: "3",
      category: "Smoke Detectors",
      question: "Condition of smoke detection systems",
      required: true,
      type: "select",
      options: [
        "Fully Functional",
        "Partially Working",
        "Not Working",
        "Not Present",
      ],
    },
    {
      id: "4",
      category: "Emergency Lighting",
      question: "Are emergency lights operational?",
      required: true,
      type: "checkbox",
    },
    {
      id: "5",
      category: "Fire Safety Plan",
      question:
        "Describe the fire safety plan and evacuation procedures",
      required: true,
      type: "text",
    },
  ];

  React.useEffect(() => {
    // Load checklist based on inspection type
    switch (selectedInspectionType) {
      case "health":
        setChecklist(healthChecklistItems);
        break;
      case "fire":
        setChecklist(fireChecklistItems);
        break;
      default:
        setChecklist(healthChecklistItems);
    }
  }, [selectedInspectionType]);

  const handleResponseChange = (
    itemId: string,
    response: any,
  ) => {
    setChecklist((prev) =>
      prev.map((item) =>
        item.id === itemId ? { ...item, response } : item,
      ),
    );
  };

  const simulateNLPAnalysis = async (
    text: string,
  ): Promise<any> => {
    setAiProcessing(true);

    // Simulate API call delay
    await new Promise((resolve) => setTimeout(resolve, 1500));

    // Mock NLP analysis
    const keywords = [
      "clean",
      "dirty",
      "good",
      "bad",
      "poor",
      "excellent",
      "violation",
      "compliant",
    ];
    const foundKeywords = keywords.filter((keyword) =>
      text.toLowerCase().includes(keyword),
    );

    let compliance:
      | "compliant"
      | "non_compliant"
      | "needs_review" = "needs_review";
    let confidence = 0.5;
    let suggestions: string[] = [];

    if (
      foundKeywords.includes("violation") ||
      foundKeywords.includes("bad") ||
      foundKeywords.includes("poor")
    ) {
      compliance = "non_compliant";
      confidence = 0.85;
      suggestions = [
        "Consider immediate corrective action",
        "Schedule follow-up inspection",
      ];
    } else if (
      foundKeywords.includes("clean") ||
      foundKeywords.includes("good") ||
      foundKeywords.includes("excellent")
    ) {
      compliance = "compliant";
      confidence = 0.9;
      suggestions = [
        "Continue current practices",
        "Maintain high standards",
      ];
    }

    setAiProcessing(false);
    return { compliance, confidence, suggestions };
  };

  const handleTextAnalysis = async (
    itemId: string,
    text: string,
  ) => {
    if (text.length > 10) {
      const analysis = await simulateNLPAnalysis(text);
      setChecklist((prev) =>
        prev.map((item) =>
          item.id === itemId
            ? { ...item, aiAnalysis: analysis }
            : item,
        ),
      );
    }
  };

  const simulateMediaAnalysis = (file: File): MediaFile => {
    // Mock OpenCV analysis
    const hazards = [
      "Fire Exit Blocked",
      "Missing PPE",
      "Overcrowding",
    ];
    const randomHazards = hazards.filter(
      () => Math.random() > 0.7,
    );

    return {
      id: Math.random().toString(36).substr(2, 9),
      name: file.name,
      type: file.type.startsWith("image/") ? "image" : "video",
      url: URL.createObjectURL(file),
      timestamp: new Date().toISOString(),
      location: "Mock Location",
      aiAnalysis: {
        hazards: randomHazards,
        confidence: 0.85,
        compliance: randomHazards.length > 0 ? "fail" : "pass",
      },
    };
  };

  const handleFileUpload = (
    event: React.ChangeEvent<HTMLInputElement>,
  ) => {
    const files = Array.from(event.target.files || []);
    files.forEach((file) => {
      const mediaFile = simulateMediaAnalysis(file);
      setMediaFiles((prev) => [...prev, mediaFile]);
    });
  };

  const calculateComplianceScore = () => {
    const completedItems = checklist.filter(
      (item) => item.response !== undefined,
    ).length;
    const totalItems = checklist.length;
    const compliantItems = checklist.filter(
      (item) => item.aiAnalysis?.compliance === "compliant",
    ).length;

    if (totalItems === 0) return 0;

    const completionScore = (completedItems / totalItems) * 50;
    const complianceScore = (compliantItems / totalItems) * 50;

    return Math.round(completionScore + complianceScore);
  };

  React.useEffect(() => {
    setComplianceScore(calculateComplianceScore());
  }, [checklist]);

  const getComplianceColor = (compliance: string) => {
    switch (compliance) {
      case "compliant":
        return "text-green-600 bg-green-50";
      case "non_compliant":
        return "text-red-600 bg-red-50";
      case "needs_review":
        return "text-yellow-600 bg-yellow-50";
      default:
        return "text-gray-600 bg-gray-50";
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0">
        <div>
          <h2 className="text-xl sm:text-2xl font-bold">
            Digital Inspection Form
          </h2>
          <p className="text-gray-600 text-sm sm:text-base">
            AI-enhanced compliance evaluation and media analysis
          </p>
        </div>
        <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
          <div className="text-left sm:text-right">
            <p className="text-sm text-gray-600">
              Compliance Score
            </p>
            <p className="text-xl sm:text-2xl font-bold text-green-600">
              {complianceScore}%
            </p>
          </div>
          <div className="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
            <Button
              variant="outline"
              className="w-full sm:w-auto"
            >
              <Save className="h-4 w-4 mr-2" />
              Save Draft
            </Button>
            <Button className="w-full sm:w-auto">
              <Send className="h-4 w-4 mr-2" />
              Submit Report
            </Button>
          </div>
        </div>
      </div>

      {/* Inspection Type Selection */}
      <Card>
        <CardHeader>
          <CardTitle>Inspection Details</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label>Inspection Type</Label>
              <Select
                value={selectedInspectionType}
                onValueChange={setSelectedInspectionType}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {Object.entries(inspectionTypes).map(
                    ([key, value]) => (
                      <SelectItem key={key} value={key}>
                        {value}
                      </SelectItem>
                    ),
                  )}
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label>Business Name</Label>
              <Input placeholder="Enter business name" />
            </div>
            <div>
              <Label>Date & Time</Label>
              <Input
                type="datetime-local"
                defaultValue={new Date()
                  .toISOString()
                  .slice(0, 16)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Progress Indicator */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium">
              Inspection Progress
            </span>
            <span className="text-sm text-gray-600">
              {
                checklist.filter(
                  (item) => item.response !== undefined,
                ).length
              }{" "}
              of {checklist.length} completed
            </span>
          </div>
          <Progress
            value={
              (checklist.filter(
                (item) => item.response !== undefined,
              ).length /
                checklist.length) *
              100
            }
            className="w-full"
          />
        </CardContent>
      </Card>

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger
            value="checklist"
            className="flex items-center space-x-2"
          >
            <FileText className="h-4 w-4" />
            <span>Checklist</span>
          </TabsTrigger>
          <TabsTrigger
            value="media"
            className="flex items-center space-x-2"
          >
            <Camera className="h-4 w-4" />
            <span>Media Upload</span>
          </TabsTrigger>
          <TabsTrigger
            value="summary"
            className="flex items-center space-x-2"
          >
            <CheckCircle className="h-4 w-4" />
            <span>Summary</span>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="checklist" className="space-y-4">
          {checklist.map((item) => (
            <Card key={item.id}>
              <CardContent className="pt-6">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <Badge variant="outline">
                        {item.category}
                      </Badge>
                      {item.required && (
                        <Badge
                          variant="destructive"
                          className="text-xs"
                        >
                          Required
                        </Badge>
                      )}
                    </div>
                    <p className="font-medium">
                      {item.question}
                    </p>
                  </div>
                  {aiProcessing && (
                    <div className="flex items-center space-x-2 text-blue-600">
                      <Zap className="h-4 w-4 animate-pulse" />
                      <span className="text-sm">
                        AI Analyzing...
                      </span>
                    </div>
                  )}
                </div>

                {/* Response Input */}
                <div className="mb-4">
                  {item.type === "checkbox" && (
                    <div className="flex items-center space-x-2">
                      <Checkbox
                        checked={item.response || false}
                        onCheckedChange={(checked) =>
                          handleResponseChange(item.id, checked)
                        }
                      />
                      <label className="text-sm">
                        Yes, compliant
                      </label>
                    </div>
                  )}

                  {item.type === "text" && (
                    <Textarea
                      value={item.response || ""}
                      onChange={(e) => {
                        handleResponseChange(
                          item.id,
                          e.target.value,
                        );
                        handleTextAnalysis(
                          item.id,
                          e.target.value,
                        );
                      }}
                      placeholder="Enter detailed observations..."
                      rows={3}
                    />
                  )}

                  {item.type === "select" && item.options && (
                    <Select
                      value={item.response || ""}
                      onValueChange={(value) =>
                        handleResponseChange(item.id, value)
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select an option" />
                      </SelectTrigger>
                      <SelectContent>
                        {item.options.map((option) => (
                          <SelectItem
                            key={option}
                            value={option}
                          >
                            {option}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  )}

                  {item.type === "number" && (
                    <Input
                      type="number"
                      value={item.response || 0}
                      onChange={(e) =>
                        handleResponseChange(
                          item.id,
                          parseInt(e.target.value),
                        )
                      }
                      placeholder="Enter number"
                    />
                  )}
                </div>

                {/* AI Analysis Results */}
                {item.aiAnalysis && (
                  <Alert
                    className={getComplianceColor(
                      item.aiAnalysis.compliance,
                    )}
                  >
                    <Zap className="h-4 w-4" />
                    <AlertDescription>
                      <div className="space-y-2">
                        <div className="flex items-center justify-between">
                          <span className="font-medium">
                            AI Analysis:{" "}
                            {item.aiAnalysis.compliance.replace(
                              "_",
                              " ",
                            )}
                          </span>
                          <Badge variant="outline">
                            {Math.round(
                              item.aiAnalysis.confidence * 100,
                            )}
                            % confidence
                          </Badge>
                        </div>
                        {item.aiAnalysis.suggestions &&
                          item.aiAnalysis.suggestions.length >
                            0 && (
                            <div>
                              <p className="text-sm font-medium">
                                Suggestions:
                              </p>
                              <ul className="text-sm list-disc ml-4">
                                {item.aiAnalysis.suggestions.map(
                                  (suggestion, idx) => (
                                    <li key={idx}>
                                      {suggestion}
                                    </li>
                                  ),
                                )}
                              </ul>
                            </div>
                          )}
                      </div>
                    </AlertDescription>
                  </Alert>
                )}
              </CardContent>
            </Card>
          ))}
        </TabsContent>

        <TabsContent value="media" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center space-x-2">
                <Camera className="h-5 w-5" />
                <span>Photo & Video Upload</span>
              </CardTitle>
              <CardDescription>
                Upload inspection photos and videos for
                AI-powered hazard detection
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                <input
                  type="file"
                  id="mediaUpload"
                  multiple
                  accept="image/*,video/*"
                  onChange={handleFileUpload}
                  className="hidden"
                />
                <label
                  htmlFor="mediaUpload"
                  className="cursor-pointer flex flex-col items-center space-y-4"
                >
                  <Upload className="h-12 w-12 text-gray-400" />
                  <div>
                    <p className="text-lg font-medium">
                      Upload Photos & Videos
                    </p>
                    <p className="text-gray-600">
                      Click to browse or drag and drop files
                    </p>
                    <p className="text-sm text-gray-500">
                      Supports JPG, PNG, MP4, MOV files
                    </p>
                  </div>
                </label>
              </div>
            </CardContent>
          </Card>

          {/* Uploaded Media */}
          {mediaFiles.length > 0 && (
            <Card>
              <CardHeader>
                <CardTitle>
                  Uploaded Media & AI Analysis
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {mediaFiles.map((file) => (
                    <div
                      key={file.id}
                      className="border rounded-lg overflow-hidden"
                    >
                      {file.type === "image" ? (
                        <img
                          src={file.url}
                          alt={file.name}
                          className="w-full h-48 object-cover"
                        />
                      ) : (
                        <video
                          src={file.url}
                          className="w-full h-48 object-cover"
                          controls
                        />
                      )}
                      <div className="p-4">
                        <p className="font-medium truncate">
                          {file.name}
                        </p>
                        <p className="text-xs text-gray-500 flex items-center mt-1">
                          <Clock className="h-3 w-3 mr-1" />
                          {new Date(
                            file.timestamp,
                          ).toLocaleString()}
                        </p>

                        {file.aiAnalysis && (
                          <div className="mt-3">
                            <div className="flex items-center justify-between mb-2">
                              <span className="text-sm font-medium">
                                AI Analysis
                              </span>
                              <Badge
                                className={
                                  file.aiAnalysis.compliance ===
                                  "pass"
                                    ? "bg-green-100 text-green-800"
                                    : file.aiAnalysis
                                          .compliance ===
                                        "warning"
                                      ? "bg-yellow-100 text-yellow-800"
                                      : "bg-red-100 text-red-800"
                                }
                              >
                                {file.aiAnalysis.compliance}
                              </Badge>
                            </div>

                            {file.aiAnalysis.hazards.length >
                              0 && (
                              <div>
                                <p className="text-xs font-medium text-red-600 mb-1">
                                  Detected Hazards:
                                </p>
                                <div className="space-y-1">
                                  {file.aiAnalysis.hazards.map(
                                    (hazard, idx) => (
                                      <Badge
                                        key={idx}
                                        variant="destructive"
                                        className="text-xs mr-1"
                                      >
                                        {hazard}
                                      </Badge>
                                    ),
                                  )}
                                </div>
                              </div>
                            )}

                            <p className="text-xs text-gray-500 mt-2">
                              Confidence:{" "}
                              {Math.round(
                                file.aiAnalysis.confidence *
                                  100,
                              )}
                              %
                            </p>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        <TabsContent value="summary" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Inspection Summary</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="text-center">
                  <div className="text-3xl font-bold text-green-600">
                    {complianceScore}%
                  </div>
                  <p className="text-sm text-gray-600">
                    Overall Compliance
                  </p>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-blue-600">
                    {
                      checklist.filter(
                        (item) => item.response !== undefined,
                      ).length
                    }
                  </div>
                  <p className="text-sm text-gray-600">
                    Items Completed
                  </p>
                </div>
                <div className="text-center">
                  <div className="text-3xl font-bold text-purple-600">
                    {mediaFiles.length}
                  </div>
                  <p className="text-sm text-gray-600">
                    Media Files
                  </p>
                </div>
              </div>

              <div>
                <Label htmlFor="inspectionNotes">
                  Inspector Notes
                </Label>
                <Textarea
                  id="inspectionNotes"
                  value={inspectionNotes}
                  onChange={(e) =>
                    setInspectionNotes(e.target.value)
                  }
                  placeholder="Add final inspection notes and recommendations..."
                  rows={4}
                />
              </div>

              <div className="border-t pt-4">
                <h4 className="font-medium mb-3">
                  Violations & Recommendations
                </h4>
                <div className="space-y-2">
                  {checklist
                    .filter(
                      (item) =>
                        item.aiAnalysis?.compliance ===
                        "non_compliant",
                    )
                    .map((item) => (
                      <Alert
                        key={item.id}
                        className="border-red-200"
                      >
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                          <span className="font-medium">
                            {item.category}:
                          </span>{" "}
                          {item.question}
                        </AlertDescription>
                      </Alert>
                    ))}

                  {mediaFiles
                    .filter(
                      (file) =>
                        file.aiAnalysis?.compliance === "fail",
                    )
                    .map((file) => (
                      <Alert
                        key={file.id}
                        className="border-red-200"
                      >
                        <AlertTriangle className="h-4 w-4" />
                        <AlertDescription>
                          <span className="font-medium">
                            Media Analysis:
                          </span>{" "}
                          {file.aiAnalysis?.hazards.join(", ")}{" "}
                          in {file.name}
                        </AlertDescription>
                      </Alert>
                    ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}