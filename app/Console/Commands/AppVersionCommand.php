<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version 
                            {--bump : Increment patch version and build number}
                            {--patch : Increment patch version (e.g. 1.0.0 -> 1.0.1)}
                            {--minor : Increment minor version (e.g. 1.0.1 -> 1.1.0)}
                            {--major : Increment major version (e.g. 1.1.0 -> 2.0.0)}
                            {--build-only : Increment build number only}
                            {--set= : Set explicit semantic version (e.g. 1.2.0)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display or bump the application version';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $versionFile = base_path('version.json');
        $jsVersionFile = resource_path('js/version.json');
        $packageJsonFile = base_path('package.json');

        $data = [
            'name' => 'Jupiter',
            'version' => '1.0.0',
            'major' => 1,
            'minor' => 0,
            'patch' => 0,
            'build' => 0,
            'build_time' => date('Y-m-d H:i:s'),
            'commit' => 'local',
            'env' => config('app.env', 'production'),
        ];

        if (file_exists($versionFile)) {
            $parsed = json_decode(file_get_contents($versionFile), true);
            if (is_array($parsed)) {
                $data = array_merge($data, $parsed);
            }
        }

        $shouldBump = $this->option('bump') || $this->option('patch') || $this->option('minor') || $this->option('major') || $this->option('build-only') || $this->option('set');

        if ($shouldBump) {
            $major = (int) ($data['major'] ?? 1);
            $minor = (int) ($data['minor'] ?? 0);
            $patch = (int) ($data['patch'] ?? 0);
            $build = ((int) ($data['build'] ?? 0)) + 1;
            $prevVersion = "{$major}.{$minor}.{$patch}";

            if ($explicit = $this->option('set')) {
                $parts = explode('.', ltrim($explicit, 'v'));
                $major = (int) ($parts[0] ?? 1);
                $minor = (int) ($parts[1] ?? 0);
                $patch = (int) ($parts[2] ?? 0);
            } elseif ($this->option('major')) {
                $major++;
                $minor = 0;
                $patch = 0;
            } elseif ($this->option('minor')) {
                $minor++;
                $patch = 0;
            } elseif (!$this->option('build-only')) {
                // Default bump or --patch
                $patch++;
            }

            $newVersion = "{$major}.{$minor}.{$patch}";

            // Git commit
            $commit = 'local';
            try {
                $commitOut = @shell_exec('git rev-parse --short HEAD');
                if ($commitOut && trim($commitOut)) {
                    $commit = trim($commitOut);
                }
            } catch (\Throwable $e) {
                $commit = $data['commit'] ?? 'local';
            }

            $now = date('Y-m-d H:i:s');
            $data['version'] = $newVersion;
            $data['major'] = $major;
            $data['minor'] = $minor;
            $data['patch'] = $patch;
            $data['build'] = $build;
            $data['build_time'] = $now;
            $data['commit'] = $commit;
            $data['env'] = config('app.env', 'production');

            $jsonString = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            file_put_contents($versionFile, $jsonString);

            if (!is_dir(dirname($jsVersionFile))) {
                @mkdir(dirname($jsVersionFile), 0755, true);
            }
            file_put_contents($jsVersionFile, $jsonString);

            if (file_exists($packageJsonFile)) {
                $pkg = json_decode(file_get_contents($packageJsonFile), true);
                if (is_array($pkg)) {
                    $pkg['version'] = $newVersion;
                    file_put_contents($packageJsonFile, json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
                }
            }

            $this->info("🚀 Version updated: v{$prevVersion} ➜ v{$newVersion} (Build {$build})");
        }

        $this->table(
            ['Property', 'Value'],
            [
                ['Application Name', $data['name'] ?? 'Jupiter'],
                ['Version', 'v' . ($data['version'] ?? '1.0.0')],
                ['Build Number', $data['build'] ?? 1],
                ['Git Commit', $data['commit'] ?? 'local'],
                ['Build Timestamp', $data['build_time'] ?? '-'],
                ['Laravel Framework', app()->version()],
                ['PHP Version', PHP_VERSION],
                ['Environment', config('app.env', 'production')],
            ]
        );

        return Command::SUCCESS;
    }
}

