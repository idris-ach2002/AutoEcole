#!/usr/bin/env python3
"""
Bootstrap multiplateforme pour AutoEcole Manager.

Objectif : détecter le système, vérifier Docker / Docker Compose, proposer une
installation adaptée, puis lancer l'application avec docker compose.
Le script reste prudent : il n'exécute les commandes d'installation qu'après
confirmation explicite.
"""
from __future__ import annotations

import argparse
import os
import platform
import shutil
import subprocess
import sys
from dataclasses import dataclass


@dataclass(frozen=True)
class Runtime:
    os_name: str
    distro: str | None
    docker: bool
    compose: bool


def run(cmd: list[str], check: bool = False) -> subprocess.CompletedProcess[str]:
    print("$ " + " ".join(cmd))
    return subprocess.run(cmd, text=True, check=check)


def detect_linux_distro() -> str | None:
    if not os.path.exists('/etc/os-release'):
        return None
    data = {}
    with open('/etc/os-release', 'r', encoding='utf-8') as fh:
        for line in fh:
            if '=' in line:
                k, v = line.strip().split('=', 1)
                data[k] = v.strip('"').lower()
    return data.get('id') or data.get('id_like')


def detect_runtime() -> Runtime:
    os_name = platform.system().lower()
    return Runtime(
        os_name=os_name,
        distro=detect_linux_distro() if os_name == 'linux' else None,
        docker=shutil.which('docker') is not None,
        compose=shutil.which('docker') is not None and subprocess.run(['docker', 'compose', 'version'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode == 0,
    )


def install_hint(runtime: Runtime) -> list[str]:
    if runtime.os_name == 'darwin':
        if shutil.which('brew'):
            return ['brew', 'install', '--cask', 'docker']
        return ['open', 'https://docs.docker.com/desktop/setup/install/mac-install/']
    if runtime.os_name == 'windows':
        if shutil.which('winget'):
            return ['winget', 'install', '-e', '--id', 'Docker.DockerDesktop']
        return ['cmd', '/c', 'start', 'https://docs.docker.com/desktop/setup/install/windows-install/']
    if runtime.os_name == 'linux':
        distro = runtime.distro or ''
        if any(x in distro for x in ['ubuntu', 'debian', 'linuxmint']):
            return ['bash', '-lc', 'sudo apt-get update && sudo apt-get install -y ca-certificates curl gnupg lsb-release docker.io docker-compose-plugin']
        if any(x in distro for x in ['fedora']):
            return ['bash', '-lc', 'sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin || sudo dnf install -y moby-engine docker-compose-plugin']
        if any(x in distro for x in ['arch', 'manjaro']):
            return ['bash', '-lc', 'sudo pacman -Sy --needed docker docker-compose']
        return ['bash', '-lc', 'echo "Distribution Linux non reconnue. Installe Docker Desktop ou Docker Engine depuis la documentation officielle."']
    return ['echo', 'Système non reconnu. Installer Docker Desktop manuellement.']


def ensure_docker(runtime: Runtime, auto_install: bool) -> None:
    if runtime.docker and runtime.compose:
        print('Docker et Docker Compose sont disponibles.')
        return
    print('Docker ou Docker Compose est absent.')
    cmd = install_hint(runtime)
    print('Commande proposée :')
    print('  ' + ' '.join(cmd))
    if not auto_install:
        print('Relance avec --install pour exécuter cette proposition, ou installe Docker manuellement.')
        sys.exit(2)
    answer = input('Exécuter cette commande ? [y/N] ').strip().lower()
    if answer != 'y':
        sys.exit(2)
    run(cmd, check=True)


def main() -> int:
    parser = argparse.ArgumentParser(description='Prépare et lance AutoEcole Manager avec Docker.')
    parser.add_argument('--install', action='store_true', help='Proposer puis exécuter l’installation Docker si nécessaire.')
    parser.add_argument('--reset', action='store_true', help='Supprimer les volumes Docker avant de relancer.')
    parser.add_argument('--no-build', action='store_true', help='Lancer sans reconstruire l’image.')
    args = parser.parse_args()

    runtime = detect_runtime()
    print(f'Système détecté : {runtime.os_name} {runtime.distro or ""}'.strip())
    ensure_docker(runtime, args.install)

    if args.reset:
        run(['docker', 'compose', 'down', '-v'], check=False)

    cmd = ['docker', 'compose', 'up']
    if not args.no_build:
        cmd.append('--build')
    run(cmd, check=True)
    return 0


if __name__ == '__main__':
    raise SystemExit(main())
