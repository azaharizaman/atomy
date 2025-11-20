<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * AI task type enumeration
 */
enum TaskType: string
{
    case ANOMALY_DETECTION = 'anomaly_detection';
    case CLASSIFICATION = 'classification';
    case PREDICTION = 'prediction';
    case FORECASTING = 'forecasting';
    case SENTIMENT_ANALYSIS = 'sentiment_analysis';
    case TEXT_GENERATION = 'text_generation';
}
