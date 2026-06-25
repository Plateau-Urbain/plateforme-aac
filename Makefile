.PHONY: all assets css bundles

# Équivalent du Makefile Symfony 3.4 + reconstruction /bundles/app/
all: assets

assets: css bundles

css: assets/css/main.css

assets/css/main.css: assets/less/main.less
	lessc assets/less/main.less assets/css/main.css

bundles:
	python3 scripts/rebuild_bundle_app_assets.py
