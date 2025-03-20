ARG NODE_VERSION=18

FROM node:$NODE_VERSION-alpine AS node

FROM publishpress/dev-workspace-terminal:generic-4.3.4

ENV PLUGIN_NAME="PublishPress Future"
ENV PLUGIN_TYPE="FREE"

COPY --from=node /usr/lib /usr/lib
COPY --from=node /usr/local/share /usr/local/share
COPY --from=node /usr/local/lib /usr/local/lib
COPY --from=node /usr/local/include /usr/local/include
COPY --from=node /usr/local/bin /usr/local/bin

COPY git/.gitconfig /root/.gitconfig

RUN npm install -g cross-env
