import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');

const versionJsonPath = path.join(rootDir, 'version.json');
const jsVersionJsonPath = path.join(rootDir, 'resources', 'js', 'version.json');
const packageJsonPath = path.join(rootDir, 'package.json');

// Read existing version data
let currentData = {
  name: 'Jupiter',
  version: '1.0.0',
  major: 1,
  minor: 0,
  patch: 0,
  build: 0,
  build_time: '',
  commit: '',
  env: 'production'
};

if (fs.existsSync(versionJsonPath)) {
  try {
    currentData = JSON.parse(fs.readFileSync(versionJsonPath, 'utf8'));
  } catch (e) {
    console.warn('[bump-version] Could not parse existing version.json, using defaults.');
  }
}

// Parse command line args
const args = process.argv.slice(2);
const isMajor = args.includes('--major');
const isMinor = args.includes('--minor');
const isBuildOnly = args.includes('--build-only');
const setIdx = args.indexOf('--set');
const explicitVersion = setIdx !== -1 && args[setIdx + 1] ? args[setIdx + 1] : null;

let major = Number(currentData.major) || 1;
let minor = Number(currentData.minor) || 0;
let patch = Number(currentData.patch) || 0;
let build = (Number(currentData.build) || 0) + 1;

const prevVersion = `${major}.${minor}.${patch}`;

if (explicitVersion) {
  const parts = explicitVersion.replace(/^v/, '').split('.').map(n => parseInt(n, 10));
  major = parts[0] || 1;
  minor = parts[1] || 0;
  patch = parts[2] || 0;
} else if (isMajor) {
  major += 1;
  minor = 0;
  patch = 0;
} else if (isMinor) {
  minor += 1;
  patch = 0;
} else if (!isBuildOnly) {
  // Default: increment patch
  patch += 1;
}

const newVersion = `${major}.${minor}.${patch}`;

// Retrieve Git commit hash
let gitCommit = 'local';
try {
  gitCommit = execSync('git rev-parse --short HEAD', { cwd: rootDir, stdio: ['pipe', 'pipe', 'ignore'] }).toString().trim();
} catch (e) {
  gitCommit = currentData.commit || 'local';
}

// Format build time: YYYY-MM-DD HH:mm:ss
const now = new Date();
const pad = (n) => String(n).padStart(2, '0');
const buildTime = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;

const updatedData = {
  name: 'Jupiter',
  version: newVersion,
  major,
  minor,
  patch,
  build,
  build_time: buildTime,
  commit: gitCommit,
  env: process.env.NODE_ENV || 'production'
};

const jsonStr = JSON.stringify(updatedData, null, 2) + '\n';

// Write version.json (root)
fs.writeFileSync(versionJsonPath, jsonStr, 'utf8');

// Write resources/js/version.json
const jsDir = path.dirname(jsVersionJsonPath);
if (!fs.existsSync(jsDir)) {
  fs.mkdirSync(jsDir, { recursive: true });
}
fs.writeFileSync(jsVersionJsonPath, jsonStr, 'utf8');

// Update package.json
if (fs.existsSync(packageJsonPath)) {
  try {
    const pkg = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    pkg.version = newVersion;
    fs.writeFileSync(packageJsonPath, JSON.stringify(pkg, null, 4) + '\n', 'utf8');
  } catch (e) {
    console.warn('[bump-version] Could not update package.json:', e.message);
  }
}

console.log(`\x1b[36m🚀 [Version Update]\x1b[0m \x1b[1mJupiter ERP\x1b[0m: v${prevVersion} (Build ${currentData.build || 0}) ➜ \x1b[32mv${newVersion} (Build ${build})\x1b[0m [commit: ${gitCommit}] [${buildTime}]`);

