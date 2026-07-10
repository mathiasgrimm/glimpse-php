.PHONY: test release check-version

# Usage:
#   make test                     run Pint, PHPStan, and the Pest suite
#   make release VERSION=vX.Y.Z   test, tag, push the tag, and create the
#                                 GitHub release; Packagist picks the new
#                                 tag up through the GitHub webhook

test:
	composer test

release: check-version
	@[ "$$(git branch --show-current)" = "main" ] || { echo "Releases are cut from main."; exit 1; }
	@[ -z "$$(git status --porcelain)" ] || { echo "Working tree is dirty; commit or stash first."; exit 1; }
	composer test
	git tag $(VERSION)
	git push origin main $(VERSION)
	gh release create $(VERSION) --title $(VERSION) --generate-notes

check-version:
	@[ -n "$(VERSION)" ] || { echo "VERSION is required, e.g. make release VERSION=v0.2.0"; exit 1; }
	@case "$(VERSION)" in v*) ;; *) echo "VERSION must be the tag name including the v prefix, e.g. v0.2.0"; exit 1; ;; esac
