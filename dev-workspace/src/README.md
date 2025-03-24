PublishPress Dev-Workspace scripts
==================================

This repository contains scripts to help you setup a development environment for PublishPress plugins.

## Requirements

- [Docker](https://docs.docker.com/install/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Git](https://git-scm.com/downloads)

## Using the dev-workspace container

### Running the dev-workspace container

To run the dev-workspace container, run the following command:

```bash
./dev-workspace/run
```

### Stopping the dev-workspace container

```bash
./dev-workspace/stop
```

### Building the dev-workspace container

If you are customizing the dev-workspace, you can build the container image running the following command:

```bash
cd ./dev-workspace
make build
```

### Building and pushing the dev-workspace container

To build the image and push it to Docker Hub, run the following command:

```bash
cd ./dev-workspace
make push
```
