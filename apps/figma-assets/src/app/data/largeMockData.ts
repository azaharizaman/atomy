// Large-scale mock data for testing Quote Comparison performance and layout
export const largeComparisonData = (() => {
  const rfqId = "RFQ-LARGE-TEST";
  const vendorCount = 12; // Large number of vendors
  const lineItemCount = 80; // 50-100 line items per quotation

  const attributes = [
    { id: "total_price", label: "Total Quote Value", type: "currency", weight: 0.4 },
    { id: "lead_time", label: "Lead Time (Weeks)", type: "number", weight: 0.2, inverse: true },
    { id: "quality_score", label: "Quality Rating", type: "score", weight: 0.2 },
    { id: "compliance", label: "Policy Compliance", type: "boolean", weight: 0.1 },
    { id: "risk_score", label: "Risk Index", type: "score", weight: 0.1, inverse: true },
  ];

  const vendorNames = [
    "Apex Industrial Solutions", "GlobalPump Corp", "Summit Flow Systems", 
    "Meridian Equipment", "Pacific Industrial Co.", "TechFlow Dynamics",
    "Nexus IT Distribution", "ServicePro Maintenance", "Delta Flow Systems",
    "Reliable Maintenance Co.", "CoreMetal Inc.", "SoftCore Licensing"
  ];

  const vendorResults = vendorNames.slice(0, vendorCount).map((name, index) => {
    const vendorId = `V${String(index + 1).padStart(3, '0')}`;
    const totalPrice = 350000 + Math.random() * 150000;
    const leadTime = 2 + Math.floor(Math.random() * 8);
    const qualityScore = 70 + Math.floor(Math.random() * 30);
    const compliance = Math.random() > 0.2;
    const riskScore = Math.floor(Math.random() * 50);
    const overallScore = Math.floor(60 + Math.random() * 35);

    const lineItems = Array.from({ length: lineItemCount }, (_, i) => ({
      id: `L${String(i + 1).padStart(3, '0')}`,
      name: `Industrial Component ${i + 1}`,
      price: 1000 + Math.random() * 5000,
      qty: 1 + Math.floor(Math.random() * 5)
    }));

    return {
      vendorId,
      name,
      totalPrice,
      leadTime,
      qualityScore,
      compliance,
      riskScore,
      overallScore,
      recommended: index === 0, // Recommend the first one for simplicity
      insights: [
        index === 0 ? "Lowest TCO" : "Competitive pricing",
        qualityScore > 90 ? "Superior quality rating" : "Standard quality",
        compliance ? "Fully compliant" : "Compliance warning"
      ],
      metrics: {
        priceIndex: (totalPrice / 400000).toFixed(2),
        qualityRank: `#${index + 1}`,
        riskLevel: riskScore < 20 ? "Low" : riskScore < 40 ? "Medium" : "High"
      },
      lineItems
    };
  });

  return {
    rfqId,
    attributes,
    vendorResults
  };
})();
