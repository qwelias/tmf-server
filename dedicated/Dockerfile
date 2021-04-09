FROM alpine

RUN apk update && apk add curl

RUN curl 'http://files2.trackmaniaforever.com/TrackmaniaServer_2011-02-21.zip' -o archive.zip && \
    unzip archive.zip && \
    rm archive.zip

CMD ["./TrackmaniaServer", "/game_settings=MatchSettings/match_settings_autosave.xml", "/dedicated_cfg=dedicated.xml", "/nodaemon"]
