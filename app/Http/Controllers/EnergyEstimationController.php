<?php

namespace App\Http\Controllers;

use App\Models\EnergyEstimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EnergyEstimationController extends Controller
{
    public function index()
    {
        $estimations = EnergyEstimation::with('components')->latest()->get();
        return view('energy-estimation.index', compact('estimations'));
    }

    public function create()
    {
        return view('energy-estimation.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'trajectory_length' => 'required|numeric|min:0.0001',
            'average_speed' => 'required|numeric|min:0.0001',
            'obstacle_count' => 'required|integer|min:0',
            'detection_threshold' => 'required|numeric|min:0.0001',
            'measurement_frequency' => 'required|integer|min:100',
            'backup_time' => 'required|numeric|min:0.0001',
            'lateral_measurement_time' => 'required|numeric|min:0.0001',
            'rotation_time' => 'required|numeric|min:0.0001',
            'battery_capacity' => 'required|numeric|min:0.0001',
            'components.static.*.name' => 'required|string|max:255',
            'components.static.*.consumption_rate' => 'required|numeric|min:0',
            'components.static.*.unit' => 'required|string|in:mWh par mesure,mWh par seconde,mWh par photo',
            'components.static.*.formula' => 'required|string|max:255',
            'components.dynamic.*.name' => 'required|string|max:255',
            'components.dynamic.*.consumption_rate' => 'required|numeric|min:0',
            'components.dynamic.*.unit' => 'required|string|in:mWh par mesure,mWh par seconde,mWh par photo',
            'components.dynamic.*.formula' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'name',
            'trajectory_length',
            'average_speed',
            'obstacle_count',
            'detection_threshold',
            'measurement_frequency',
            'backup_time',
            'lateral_measurement_time',
            'rotation_time',
            'battery_capacity',
        ]);

        $estimation = EnergyEstimation::create($data);

        // Enregistrer les composants statiques
        if ($request->has('components.static')) {
            foreach ($request->components['static'] as $componentData) {
                if (!$this->isValidFormula($componentData['formula'])) {
                    return redirect()->back()->withErrors(['components' => 'Formule invalide pour le composant : ' . $componentData['name']])->withInput();
                }
                $estimation->components()->create($componentData);
            }
        }

        // Enregistrer les composants dynamiques
        if ($request->has('components.dynamic')) {
            foreach ($request->components['dynamic'] as $componentData) {
                if (!$this->isValidFormula($componentData['formula'])) {
                    return redirect()->back()->withErrors(['components' => 'Formule invalide pour le composant : ' . $componentData['name']])->withInput();
                }
                $estimation->components()->create($componentData);
            }
        }

        return redirect()->route('energy-estimation.index')->with('success', 'Estimation créée avec succès.');
    }

    public function show(EnergyEstimation $energyEstimation)
    {
        $results = $this->calculateResults($energyEstimation);
        return view('energy-estimation.show', compact('energyEstimation', 'results'));
    }

    public function edit(EnergyEstimation $energyEstimation)
    {
        return view('energy-estimation.edit', compact('energyEstimation'));
    }

    public function update(Request $request, EnergyEstimation $energyEstimation)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'trajectory_length' => 'required|numeric|min:0.0001',
            'average_speed' => 'required|numeric|min:0.0001',
            'obstacle_count' => 'required|integer|min:0',
            'detection_threshold' => 'required|numeric|min:0.0001',
            'measurement_frequency' => 'required|integer|min:100',
            'backup_time' => 'required|numeric|min:0.0001',
            'lateral_measurement_time' => 'required|numeric|min:0.0001',
            'rotation_time' => 'required|numeric|min:0.0001',
            'battery_capacity' => 'required|numeric|min:0.0001',
            'components.static.*.name' => 'required|string|max:255',
            'components.static.*.consumption_rate' => 'required|numeric|min:0',
            'components.static.*.unit' => 'required|string|in:mWh par mesure,mWh par seconde,mWh par photo',
            'components.static.*.formula' => 'required|string|max:255',
            'components.dynamic.*.name' => 'required|string|max:255',
            'components.dynamic.*.consumption_rate' => 'required|numeric|min:0',
            'components.dynamic.*.unit' => 'required|string|in:mWh par mesure,mWh par seconde,mWh par photo',
            'components.dynamic.*.formula' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->only([
            'name',
            'trajectory_length',
            'average_speed',
            'obstacle_count',
            'detection_threshold',
            'measurement_frequency',
            'backup_time',
            'lateral_measurement_time',
            'rotation_time',
            'battery_capacity',
        ]);

        $energyEstimation->update($data);
        $energyEstimation->components()->delete();

        // Enregistrer les composants statiques
        if ($request->has('components.static')) {
            foreach ($request->components['static'] as $componentData) {
                if (!$this->isValidFormula($componentData['formula'])) {
                    return redirect()->back()->withErrors(['components' => 'Formule invalide pour le composant : ' . $componentData['name']])->withInput();
                }
                $energyEstimation->components()->create($componentData);
            }
        }

        // Enregistrer les composants dynamiques
        if ($request->has('components.dynamic')) {
            foreach ($request->components['dynamic'] as $componentData) {
                if (!$this->isValidFormula($componentData['formula'])) {
                    return redirect()->back()->withErrors(['components' => 'Formule invalide pour le composant : ' . $componentData['name']])->withInput();
                }
                $energyEstimation->components()->create($componentData);
            }
        }

        return redirect()->route('energy-estimation.index')->with('success', 'Estimation mise à jour avec succès.');
    }

    public function destroy(EnergyEstimation $energyEstimation)
    {
        $energyEstimation->delete();
        return redirect()->route('energy-estimation.index')->with('success', 'Estimation supprimée avec succès.');
    }

    public function exportCsv()
    {
        $estimations = EnergyEstimation::with('components')->get();
        $filename = 'energy_estimations_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $columns = [
            'ID', 'Nom', 'Longueur Trajectoire (m)', 'Vitesse Moyenne (m/s)', 'Nombre Obstacles',
            'Seuil Détection (cm)', 'Fréquence Mesure (ms)', 'Temps Recul (s)', 'Temps Mesure Latérale (s)',
            'Temps Rotation (s)', 'Capacité Batterie (mWh)', 'Composants', 'Créé le',
        ];

        $callback = function () use ($estimations, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for UTF-8
            fputcsv($file, $columns);

            foreach ($estimations as $estimation) {
                $components = $estimation->components->map(function ($component) {
                    return sprintf(
                        '%s: %s %s (%s)',
                        $component->name,
                        $component->consumption_rate,
                        $component->unit,
                        $component->formula
                    );
                })->implode('; ');

                fputcsv($file, [
                    $estimation->id,
                    $estimation->name ?: 'Sans nom',
                    $estimation->trajectory_length,
                    $estimation->average_speed,
                    $estimation->obstacle_count,
                    $estimation->detection_threshold,
                    $estimation->measurement_frequency,
                    $estimation->backup_time,
                    $estimation->lateral_measurement_time,
                    $estimation->rotation_time,
                    $estimation->battery_capacity,
                    $components,
                    $estimation->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportSingleCsv(EnergyEstimation $energyEstimation)
    {
        $results = $this->calculateResults($energyEstimation);
        $filename = 'energy_estimation_' . ($energyEstimation->name ? \Str::slug($energyEstimation->name) : $energyEstimation->id) . '_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $columns = [
            'ID', 'Nom', 'Longueur Trajectoire (m)', 'Vitesse Moyenne (m/s)', 'Nombre Obstacles',
            'Seuil Détection (cm)', 'Fréquence Mesure (ms)', 'Temps Recul (s)', 'Temps Mesure Latérale (s)',
            'Temps Rotation (s)', 'Capacité Batterie (mWh)', 'Durée Totale (s)', 'Consommation Totale (mWh)',
            'Autonomie Restante (mWh)', 'Composants',
        ];

        $callback = function () use ($energyEstimation, $results, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM for UTF-8
            fputcsv($file, $columns);

            $components = $energyEstimation->components->map(function ($component) use ($results) {
                return sprintf(
                    '%s: %s %s (%s, Consommation: %s mWh)',
                    $component->name,
                    $component->consumption_rate,
                    $component->unit,
                    $component->formula,
                    number_format($results['components'][$component->id]['consumption'], 4)
                );
            })->implode('; ');

            fputcsv($file, [
                $energyEstimation->id,
                $energyEstimation->name ?: 'Sans nom',
                number_format($energyEstimation->trajectory_length, 4),
                number_format($energyEstimation->average_speed, 4),
                $energyEstimation->obstacle_count,
                number_format($energyEstimation->detection_threshold, 4),
                $energyEstimation->measurement_frequency,
                number_format($energyEstimation->backup_time, 4),
                number_format($energyEstimation->lateral_measurement_time, 4),
                number_format($energyEstimation->rotation_time, 4),
                number_format($energyEstimation->battery_capacity, 4),
                number_format($results['total_duration'], 4),
                number_format($results['total_consumption'], 4),
                number_format($results['remaining_autonomy'], 4),
                $components,
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function calculateResults(EnergyEstimation $estimation)
    {
        $forward_movement_duration = $estimation->trajectory_length / $estimation->average_speed;
        $maneuver_time_per_obstacle = $estimation->backup_time + $estimation->lateral_measurement_time + $estimation->rotation_time;
        $total_maneuver_time = $estimation->obstacle_count * $maneuver_time_per_obstacle;
        $total_duration = $forward_movement_duration + $total_maneuver_time;

        $measurements_during_movement = ceil(($total_duration * 1000) / $estimation->measurement_frequency);
        $lateral_measurements_per_obstacle = ceil(($estimation->lateral_measurement_time * 1000) / $estimation->measurement_frequency);
        $total_lateral_measurements = $estimation->obstacle_count * $lateral_measurements_per_obstacle * 2;
        $total_measurements = $measurements_during_movement + $total_lateral_measurements;
        $photos_taken = $estimation->obstacle_count;

        $total_consumption = 0;
        $component_results = [];

        foreach ($estimation->components as $component) {
            $energy = 0;
            try {
                $formula = $this->replaceFormulaVariables($component->formula, [
                    'value' => $component->consumption_rate,
                    'total_measurements' => $total_measurements,
                    'photos_taken' => $photos_taken,
                    'forward_movement_duration' => $forward_movement_duration,
                    'total_maneuver_time' => $total_maneuver_time,
                    'lateral_measurement_time' => $estimation->lateral_measurement_time,
                    'rotation_time' => $estimation->rotation_time,
                    'total_duration' => $total_duration,
                    'obstacle_count' => $estimation->obstacle_count,
                ]);
                $energy = $this->evaluateFormula($formula);
                if (is_nan($energy) || !is_finite($energy)) {
                    $energy = 0;
                }
            } catch (\Exception $e) {
                Log::error("Erreur dans l'évaluation de la formule pour le composant {$component->name}: " . $e->getMessage());
                $energy = 0;
            }
            $total_consumption += $energy;
            $component_results[$component->id] = [
                'name' => $component->name,
                'consumption' => $energy,
            ];
        }

        return [
            'forward_movement_duration' => $forward_movement_duration,
            'total_maneuver_time' => $total_maneuver_time,
            'total_duration' => $total_duration,
            'total_measurements' => $total_measurements,
            'photos_taken' => $photos_taken,
            'total_consumption' => $total_consumption,
            'total_consumption_wh' => $total_consumption / 1000,
            'remaining_autonomy' => $estimation->battery_capacity - $total_consumption,
            'components' => $component_results,
        ];
    }

    protected function isValidFormula($formula)
    {
        $allowedVariables = [
            'value',
            'total_measurements',
            'photos_taken',
            'forward_movement_duration',
            'total_maneuver_time',
            'lateral_measurement_time',
            'rotation_time',
            'total_duration',
            'obstacle_count',
        ];

        $pattern = '/^[0-9+\-*\/()\s.' . implode('|', $allowedVariables) . ']+$/';
        return preg_match($pattern, $formula);
    }

    protected function replaceFormulaVariables($formula, $variables)
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements[$key] = is_numeric($value) ? $value : 0;
        }
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $formula
        );
    }

    protected function evaluateFormula($formula)
    {
        try {
            return eval('return ' . $formula . ';');
        } catch (\Exception $e) {
            throw new \Exception('Erreur dans l\'évaluation de la formule');
        }
    }
}