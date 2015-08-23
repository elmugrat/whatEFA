# whatEFA
Simple PHP Connector for EFA

####What It Does
EFA (Elektronische Fahrplanauskunft) is the service used by Germany's regional and long distance public transit agencies for getting trip information.  It also provides arrival and departure monitoring for all of the bus stops and train stations in Germany.

whatEFA handles the connection to EFA to simplify retrieving transit stop departure times.  Given the city and approximate stop name, it looks up the stop and gets the upcoming departures.

####Usage
`GET` whatEFA.php with the following parameters:

- `city`: The desired city
- `stop`: The name of the transit stop (typos and common abbreviations like 'hbf' or 'str' are allowed)
- `limit`: The max number of departures returned (default: 20)

```javascript
var params = {
	city: 'hannover',
	stop: 'hbf',
	limit: 20	
};
				
$.get('whatEFA.php', params, function(data) {
	// do something cool!
});
```

####Success
A successful request returns a JSON object with the following structure: 
```json
{
  "status": 200,
  "state": "success",
  "data": {
    "stopName": "Hauptbahnhof",
    "stopLongName": "Hannover Hauptbahnhof",
    "platforms": {
      "1": {
        "name": "2",
        "transitLines": {
          "erx 83024": {
            "directionTo": "Walsrode Bahnhof",
            "directionFrom": "Hannover Hauptbahnhof",
            "type": "erixx",
            "departures": [
              1440341460
            ]
          }
        }
      },
      "SB 2": {
        "name": "SB 2",
        "transitLines": {
          "17": {
            "directionTo": "Hannover/Wallensteinstraße",
            "directionFrom": "Hannover/Aegidientorplatz",
            "type": "Stadtbahn",
            "departures": [
              1440341520
            ]
          }
        }
      }
    }
  }
}
```

* `status`: HTTP Status Code
* `state`: 'error' or 'success'
* `data`: The Good Stuff
  * `stopName`: The transit stop name
  * `stopLongName`: City + transit stop name
  * `platforms`: A list of the platforms/directional bus stops
    * `key`: Platform "number".  The namimg scheme varies by city
      * `name`: The real name of the platform.  Note differences from `key`!
      * `transitLines`: A list of transit lines departing soon
        * `key`: Train/transit line identifier
          * `directionTo`: Where it's going
          * `directionFrom`: Where it's coming from
          * `type`: Vehicle type (ex: Bus/Straßenbahn/ICE/RB etc)
          * `departures`: An array of the next departures in Unix time

####Error
Errors return with an appropriate HTTP status code and a JSON object with error details.  

```json
{
  "status": 400,
  "state": "error",
  "message": "Parameter missing: [stop]"
}
```

* `status`: HTTP Status Code
* `state`: 'error' or 'success'
* `message`: Error details

whatEFA currently uses the following HTTP codes:

* `405`: Request method not supported (only supports `GET`)
* `400`: Parameters are missing or invalid
* `404`: No matching transit stop found, or no departures found
* `503`: Couldn't communicate with the EFA server (happens frequently)

