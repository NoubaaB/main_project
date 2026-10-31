<?php

namespace App\GraphQL;

class CommonGraphQL
{
    public static function getSDL(): string
    {
        return <<<GQL
type Point2D {
    x: String
    y: Float
    label: String
}

type StockableItem {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
    name_in_invoice: String
    type: String
}

type PackageType {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
    weight: Float
}

type Supplier {
    id: ID!
    name: String
    phone: String
    email: String
    balance: Float
}

type Client {
    id: ID!
    name: String
    phone: String
    email: String
    balance: Float
}

type Fishery {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
}

type Ship {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
}

type Origin {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
}

type Truck {
    id: ID!
    name: String
    name_fr: String
    name_en: String
    name_es: String
}

type QualityControlSummary {
    id: ID
    status: String
    overall_score: Float
}

type InvoiceSummary {
    id: ID
    date: String
    tracking_numbers: [String]
    total_price: Float
    total_weight: Float
    status: String
}

type ChargeSummary {
    id: ID
    date: String
    reception_tracking_number: [String]
    total_weight: Float
}

type UserSummary {
    id: ID
    name: String
    email: String
}
GQL;
    }
}

