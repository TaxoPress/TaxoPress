#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to validate version number format (x.x.x)
validate_version() {
    if ! [[ $1 =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9.]+)?$ ]]; then
        echo "Invalid version format. Please use x.x.x or x.x.x-beta.x"
        exit 1
    fi
}

# Function to create release checklist
create_checklist() {
    cat dev-workspace/pr-template.md | sed "s/\$1/$1/g"
}

# Get current branch
current_branch=$(git branch --show-current)

# Ensure we're on main/master branch
if [[ "$current_branch" != "main" && "$current_branch" != "master" ]]; then
    echo -e "${YELLOW}Warning: You're not on main/master branch. Current branch: $current_branch${NC}"
    read -p "Do you want to continue? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Ensure working directory is clean
if [[ -n $(git status -s) ]]; then
    echo "Error: Working directory is not clean. Please commit or stash changes first."
    exit 1
fi

# Get latest changes from remote
echo "Fetching latest changes..."
git fetch origin

# Prompt for version number
read -p "Enter the version number to release (x.x.x): " version

# Validate version number
validate_version $version

# Create branch name
branch_name="release-$version"

# Check if the current branch already exists. If not, create it.
if ! git show-ref --verify --quiet refs/heads/$branch_name; then
    echo -e "${GREEN}Creating branch $branch_name...${NC}"
    git checkout -b $branch_name
fi

# If not in the release branch, checkout to it.
if [[ "$(git branch --show-current)" != "$branch_name" ]]; then
    git checkout $branch_name
fi

# Push branch to remote, if not already pushed.
if ! git push -u origin $branch_name; then
    echo -e "${GREEN}Branch $branch_name already pushed to remote.${NC}"
fi

# Generate checklist
checklist=$(create_checklist $version)

# Create pull request using GitHub CLI
if command -v gh &> /dev/null; then
# Check gh is authenticated
    if ! gh auth status &> /dev/null; then
        echo -e "${YELLOW}Warning: GitHub credentials are not set up.${NC}"

        # Set gh token
        source .env
        gh auth login --with-token <<< $GITHUB_ACCESS_TOKEN

        if ! gh auth status &> /dev/null; then
            echo -e "${RED}Failed to set up GitHub credentials.${NC}"
            exit 1
        fi
    fi

    echo -e "${GREEN}Creating pull request...${NC}"
    gh pr create \
        --title "Release $version" \
        --body "$checklist" \
        --base main \
        --head $branch_name

    echo -e "${GREEN}Pull request created successfully!${NC}"
else
    echo "GitHub CLI (gh) is not installed. Please install it to create pull requests automatically."
    echo "You can create the pull request manually with this checklist:"
    echo "$checklist"
fi
