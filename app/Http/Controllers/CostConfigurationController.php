<?php

namespace App\Http\Controllers;

use App\Models\CostConfiguration;
use App\Models\ChargeCategory;
use App\Models\ProductionCategory;
use App\Models\ShippingCategory;
use App\Models\Charge;
use App\Models\Production;
use App\Models\Shipping;
use App\Models\StorageInvoice;
use App\Models\FortnightActivity;
use App\Traits\BroadcastsModelChanges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CostConfigurationController extends Controller
{
    use BroadcastsModelChanges;

    /**
     * Display a listing of cost configurations.
     */
    public function index()
    {
        $costConfigurations = CostConfiguration::with('creator:id,name')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'cost_configurations' => $costConfigurations,
        ]);
    }

    /**
     * Store a newly created cost configuration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_fr'      => 'required|string|max:255',
            'name_en'      => 'nullable|string|max:255',
            'name_es'      => 'nullable|string|max:255',
            'unit'         => 'required|string|max:50',
            'variables'    => 'required|array|min:1',
            'variables.*.var_name'    => 'required|string|max:50',
            'variables.*.data_source' => 'required|string|in:charge,production,shipping,storage_invoice,fortnight_activities',
            'variables.*.category_id' => 'nullable|integer',
            'variables.*.value_type'  => 'required|string|in:price,weight',
            'formula'      => 'required|string|max:1000',
            'description'  => 'nullable|string|max:2000',
            'enabled'      => 'nullable|boolean',
            'show_in_chart' => 'nullable|boolean',
            'chart_color'  => 'nullable|string|max:9',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['enabled'] = $validated['enabled'] ?? true;

        $costConfiguration = CostConfiguration::create($validated);
        $costConfiguration->load('creator:id,name');

        $this->broadcastChange('CostConfiguration', 'created', $costConfiguration->id, $costConfiguration->toArray());

        return response()->json([
            'message' => 'Cost configuration created successfully',
            'cost_configuration' => $costConfiguration,
        ], 201);
    }

    /**
     * Display the specified cost configuration.
     */
    public function show(CostConfiguration $costConfiguration)
    {
        $costConfiguration->load('creator:id,name');
        return response()->json([
            'cost_configuration' => $costConfiguration,
        ]);
    }

    /**
     * Update the specified cost configuration.
     */
    public function update(Request $request, CostConfiguration $costConfiguration)
    {
        $validated = $request->validate([
            'name_fr'      => 'required|string|max:255',
            'name_en'      => 'nullable|string|max:255',
            'name_es'      => 'nullable|string|max:255',
            'unit'         => 'required|string|max:50',
            'variables'    => 'required|array|min:1',
            'variables.*.var_name'    => 'required|string|max:50',
            'variables.*.data_source' => 'required|string|in:charge,production,shipping,storage_invoice,fortnight_activities',
            'variables.*.category_id' => 'nullable|integer',
            'variables.*.value_type'  => 'required|string|in:price,weight',
            'formula'      => 'required|string|max:1000',
            'description'  => 'nullable|string|max:2000',
            'enabled'      => 'nullable|boolean',
            'show_in_chart' => 'nullable|boolean',
            'chart_color'  => 'nullable|string|max:9',
        ]);

        $oldData = $costConfiguration->toArray();
        $costConfiguration->update($validated);
        $costConfiguration->load('creator:id,name');

        $this->broadcastChange('CostConfiguration', 'updated', $costConfiguration->id, $costConfiguration->toArray(), $oldData);

        return response()->json([
            'message' => 'Cost configuration updated successfully',
            'cost_configuration' => $costConfiguration,
        ]);
    }

    /**
     * Remove the specified cost configuration from storage.
     */
    public function destroy(CostConfiguration $costConfiguration)
    {
        $oldData = $costConfiguration->toArray();
        $recordId = $costConfiguration->id;

        $costConfiguration->delete();

        $this->broadcastChange('CostConfiguration', 'deleted', $recordId, [], $oldData);

        return response()->json([
            'message' => 'Cost configuration deleted successfully',
        ]);
    }

    /**
     * Fetch available data source categories (Charge, Production, Shipping)
     * and live aggregate reference values for testing.
     */
    public function categoriesAndSources()
    {
        $chargeCategories = ChargeCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')
            ->orderBy('name_fr')
            ->get();

        $productionCategories = ProductionCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')
            ->orderBy('name_fr')
            ->get();

        $shippingCategories = ShippingCategory::select('id', 'name', 'name_fr', 'name_en', 'name_es')
            ->orderBy('name_fr')
            ->get();

        return response()->json([
            'categories' => [
                'charge'     => $chargeCategories,
                'production' => $productionCategories,
                'shipping'   => $shippingCategories,
            ],
            'data_sources' => [
                [
                    'key' => 'charge',
                    'label_fr' => 'Frais / Charges',
                    'label_en' => 'Charges',
                    'label_es' => 'Gastos / Cargos',
                    'has_categories' => true,
                    'allows_weight' => true,
                ],
                [
                    'key' => 'production',
                    'label_fr' => 'Production',
                    'label_en' => 'Production',
                    'label_es' => 'Producción',
                    'has_categories' => true,
                    'allows_weight' => true,
                ],
                [
                    'key' => 'shipping',
                    'label_fr' => 'Expédition / Shipping',
                    'label_en' => 'Shipping',
                    'label_es' => 'Envíos / Embarques',
                    'has_categories' => true,
                    'allows_weight' => true,
                ],
                [
                    'key' => 'storage_invoice',
                    'label_fr' => 'Facture de Stockage',
                    'label_en' => 'Storage Invoice',
                    'label_es' => 'Factura Almacenamiento',
                    'has_categories' => false,
                    'allows_weight' => true,
                ],
                [
                    'key' => 'fortnight_activities',
                    'label_fr' => 'Activités Quinzaine',
                    'label_en' => 'Fortnight Activities',
                    'label_es' => 'Actividades Quincenales',
                    'has_categories' => false,
                    'allows_weight' => false, // Default to price only
                ],
            ],
        ]);
    }

    /**
     * Safely evaluate a math formula string with variable values.
     */
    public function evaluate(Request $request)
    {
        $request->validate([
            'formula' => 'required|string',
            'values'  => 'required|array',
        ]);

        $formula = $request->input('formula');
        $values = $request->input('values');

        $result = $this->calculateFormula($formula, $values);

        return response()->json($result);
    }

    /**
     * Compute formula safely using token substitution and math parser.
     */
    private function calculateFormula(string $formula, array $values): array
    {
        try {
            // Clean formula string
            $expr = trim($formula);

            // Replace variable identifiers (e.g. c1, c2, c3...) with numbers
            // Sort variable names descending by length so c10 is replaced before c1
            uksort($values, function ($a, $b) {
                return strlen($b) <=> strlen($a);
            });

            foreach ($values as $varName => $val) {
                $numVal = is_numeric($val) ? (float)$val : 0;
                // Replace word-bounded variable name or string match
                $pattern = '/\b' . preg_quote($varName, '/') . '\b/';
                $expr = preg_replace($pattern, (string)$numVal, $expr);
            }

            // Sanitize: allow only numbers, +, -, *, /, %, (, ), ., spaces
            if (preg_match('/[^0-9\+\-\*\/\%\(\)\.\s]/', $expr)) {
                return [
                    'success' => false,
                    'message' => 'Formula contains invalid characters',
                    'result'  => null,
                ];
            }

            // Protect against zero division or syntax errors
            // Use safe mathematical evaluation via custom tokenizer / parser or math expression evaluator
            $computed = $this->evalMathExpression($expr);

            if ($computed === null || is_nan($computed) || is_infinite($computed)) {
                return [
                    'success' => false,
                    'message' => 'Calculation error or division by zero',
                    'result'  => null,
                ];
            }

            return [
                'success' => true,
                'result'  => round($computed, 4),
                'evaluated_expression' => $expr,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Invalid formula syntax: ' . $e->getMessage(),
                'result'  => null,
            ];
        }
    }

    /**
     * Lightweight safe math parser in pure PHP without eval().
     */
    private function evalMathExpression(string $expr): ?float
    {
        $expr = str_replace(' ', '', $expr);
        if ($expr === '') return 0.0;

        // Convert percentage operator e.g. 50% to (50/100) or Handle % as modulus or percent
        // If % is preceded by a number and followed by non-number/end, convert to /100
        $expr = preg_replace('/(\d+(\.\d+)?)%/', '($1/100)', $expr);

        // Recursive evaluation of parentheses
        while (preg_match('/\(([^()]+)\)/', $expr, $matches)) {
            $subVal = $this->evalSimpleMath($matches[1]);
            if ($subVal === null) return null;
            $expr = str_replace($matches[0], (string)$subVal, $expr);
        }

        return $this->evalSimpleMath($expr);
    }

    private function evalSimpleMath(string $expr): ?float
    {
        // Tokenize numbers and operators
        preg_match_all('/(?:\d+\.?\d*|\.\d+)|[\+\-\*\/\%]/', $expr, $tokens);
        $tokens = $tokens[0] ?? [];

        if (empty($tokens)) return null;

        // Handle negative starting number or unary operators
        $numTokens = [];
        $i = 0;
        $cnt = count($tokens);

        while ($i < $cnt) {
            $t = $tokens[$i];
            if (($t === '-' || $t === '+') && ($i === 0 || in_array($tokens[$i - 1], ['+', '-', '*', '/', '%']))) {
                $next = $tokens[$i + 1] ?? '0';
                $numTokens[] = (float)($t . $next);
                $i += 2;
            } else if (is_numeric($t)) {
                $numTokens[] = (float)$t;
                $i++;
            } else {
                $numTokens[] = $t;
                $i++;
            }
        }

        // Pass 1: Multiplication, Division, Modulus
        $pass1 = [];
        $j = 0;
        $numCnt = count($numTokens);

        while ($j < $numCnt) {
            $token = $numTokens[$j];
            if ($token === '*' || $token === '/' || $token === '%') {
                $prev = array_pop($pass1);
                $next = $numTokens[$j + 1] ?? 0;
                if ($token === '*') {
                    $pass1[] = $prev * $next;
                } else if ($token === '/') {
                    if ((float)$next === 0.0) return null; // Division by zero
                    $pass1[] = $prev / $next;
                } else if ($token === '%') {
                    if ((float)$next === 0.0) return null;
                    $pass1[] = fmod($prev, $next);
                }
                $j += 2;
            } else {
                $pass1[] = $token;
                $j++;
            }
        }

        // Pass 2: Addition, Subtraction
        if (empty($pass1)) return 0.0;
        $res = is_numeric($pass1[0]) ? (float)$pass1[0] : 0.0;
        $k = 1;
        $p1Cnt = count($pass1);

        while ($k < $p1Cnt) {
            $op = $pass1[$k];
            $val = $pass1[$k + 1] ?? 0;
            if ($op === '+') {
                $res += (float)$val;
            } else if ($op === '-') {
                $res -= (float)$val;
            }
            $k += 2;
        }

        return $res;
    }
}
