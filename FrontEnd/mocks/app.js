const express = require('express')
const bodyParser = require('body-parser')
const rules = require('./rules')

const app = express()

app.use(bodyParser.json())
app.use(bodyParser.urlencoded({ extended: false }))

app.use('/api', rules)

app.listen(3001, () => {
  console.log('Mock server is running at http://localhost:3001')
})
