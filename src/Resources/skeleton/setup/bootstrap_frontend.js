import { Application } from "@hotwired/stimulus"
import { definitionsFromContext } from "@hotwired/stimulus-webpack-helpers"

// We can't use Stimulus Bridge twice, so import it here the native way
window.Stimulus = Application.start()
const context = require.context("./controllers_frontend", true, /\.js$/)
Stimulus.load(definitionsFromContext(context))
