#!/usr/bin/env python3
from pathlib import Path
import os
import shutil


def recreate_public_bundle_app() -> None:
    root = Path(__file__).resolve().parents[1]
    bundle_app = root / "public" / "bundles" / "app"

    if bundle_app.exists() or bundle_app.is_symlink():
        if bundle_app.is_symlink() or bundle_app.is_file():
            bundle_app.unlink()
        else:
            shutil.rmtree(bundle_app)

    bundle_app.mkdir(parents=True, exist_ok=True)

    # Keep legacy URLs /bundles/app/{css,js,...} working from assets/.
    for name in ("css", "js", "langs", "rs-plugin", "fonts"):
        os.symlink(Path("../../../assets") / name, bundle_app / name)

    # Merge both historical images sources under /bundles/app/images:
    # - assets/images (current front assets)
    # - public/images (legacy uploaded/static assets)
    images_dir = bundle_app / "images"
    images_dir.mkdir(parents=True, exist_ok=True)

    for source in (root / "assets" / "images", root / "public" / "images"):
        for path in source.rglob("*"):
            rel = path.relative_to(source)
            dest = images_dir / rel

            if path.is_dir():
                dest.mkdir(parents=True, exist_ok=True)
                continue

            dest.parent.mkdir(parents=True, exist_ok=True)
            if dest.exists() or dest.is_symlink():
                continue

            target = Path(os.path.relpath(path, dest.parent))
            os.symlink(target, dest)


if __name__ == "__main__":
    recreate_public_bundle_app()
