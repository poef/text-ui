dialog #mydialog
	h1 dc:title "Sign Up"
	input schema:email "Email"
	input password
	input password "Confirm Password"
	button "Sign Up" .default
	button "Log In"

---

[
	{
		"element": "dialog",
		"id": "mydialog",
		"children": [
			{
				"element": "h1",
				"attributes": [
					{
						"name": "dc:title"
					}
				],
				"label": "Sign Up"
			},
			{
				"element": "input",
				"attributes": [
					{
						"name": "schema:email"
					}
				],
				"label": "Email"
			},
			....
		]
	}
]