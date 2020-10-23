<?php

namespace whatwedo\CrudBundle\Traits;

trait VoterAttributeTrait
{
    /**
     * @return string
     */
    public function getShowVoterAttribute()
    {
        return $this->options['show_voter_attribute'];
    }

    /**
     * @return string
     */
    public function getEditVoterAttribute()
    {
        return $this->options['edit_voter_attribute'];
    }

    /**
     * @return string
     */
    public function getCreateVoterAttribute()
    {
        return $this->options['create_voter_attribute'];
    }
}
