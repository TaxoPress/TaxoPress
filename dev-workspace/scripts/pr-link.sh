#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color
PLUGIN_REPO="publishpress/publishpress-future"

# Function to validate version number format (x.x.x)
validate_version() {
    if ! [[ $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.]+)?$ ]]; then
        echo "Invalid version format. Please use x.x.x or x.x.x-beta.x"
        exit 1
    fi
}

# Get current branch
current_branch=$(git branch --show-current)

# Prompt for version number
read -p "Enter the version number to release (x.x.x): " version

# Validate version number
validate_version $version

# Create branch name
branch_name="release-$version"

# Look for the pull request for the current release branch.
pr_number=$(gh pr list --state open --search $branch_name --json number --jq '.[0].number')

echo "https://github.com/$PLUGIN_REPO/pull/$pr_number"
